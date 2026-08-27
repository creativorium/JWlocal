/**
 * JW Trading — Mentorship funnel → Google Sheet receiver.
 *
 * Paste into the sheet's Apps Script editor (Extensions → Apps Script), set
 * SECRET below, then Deploy → New deployment → Web app:
 *   Execute as:      Me
 *   Who has access:  Anyone
 * Copy the /exec URL into wp-admin → Mentorship → Pengaturan → Webhook URL,
 * and put the same SECRET in the "Shared secret" field.
 *
 * ONE ROW PER PERSON. WordPress posts twice per lead — once at opt-in and once
 * at application — both carrying the same `lead_id`. This script UPSERTS on that
 * id, so the opt-in creates the row and the application fills in the rest.
 * People who opt in and never apply keep a row with Status = optin, which is the
 * whole point: you can see who dropped off.
 *
 * Question columns are created on demand from the question TEXT, so the
 * application questions can be reworded or added to without touching this file.
 */

var SECRET = 'CHANGE-ME';        // Must match the WP "Shared secret" field.
var SHEET  = 'Mentorship Leads'; // Tab name; created automatically.

// The spreadsheet to write into, taken from its URL:
//   docs.google.com/spreadsheets/d/<THIS PART>/edit
// Setting it explicitly means the script works whether it lives inside the
// sheet (Extensions -> Apps Script) or as a standalone project -- a standalone
// project has no "active" spreadsheet and getActiveSpreadsheet() returns null.
// Leave blank ONLY if the script is bound to the sheet.
var SHEET_ID = '164sA8-HsdkIRe8mlzd5jG1k_K_evVIkwWRFcj577IbQ';

var TIMEZONE = 'Asia/Jakarta';

// Bumped whenever this file changes. Open the /exec URL in a browser: the
// version it reports tells you which code the DEPLOYMENT is actually serving.
// Editing and saving does NOT change it -- only Deploy > Manage deployments >
// edit > New version does.
var VERSION = '2026-08-27-b';

// Fixed leading columns. Anything after these is a question column.
// "Lead ID" must stay column A -- it is the key the upsert matches on -- but it
// is hidden on creation, so the sheet reads as if it starts at Status.
var FIXED = [
  'Lead ID',
  'Status',
  'Tanggal',
  'Nama',
  'Email',
  'WhatsApp',
  'Source'
];

function doPost(e) {
  // Serialise: two people submitting at once must not claim the same row.
  var lock = LockService.getScriptLock();
  try {
    lock.waitLock(30000);
  } catch (err) {
    return reply(false, 'busy');
  }

  try {
    var body = JSON.parse((e && e.postData && e.postData.contents) || '{}');

    if (String(body.secret || '') !== SECRET) {
      return reply(false, 'bad secret');
    }

    var leadId = Number(body.lead_id || 0);
    if (!leadId) {
      return reply(false, 'missing lead_id');
    }

    var sheet   = getSheet();
    var headers = getHeaders(sheet);
    var isApply = String(body.type || '') === 'mentorship_application';

    // Make sure every question in this payload has a column.
    var answers = Array.isArray(body.answers) ? body.answers : [];
    for (var i = 0; i < answers.length; i++) {
      var q = String((answers[i] && answers[i].q) || '').trim();
      if (q && headers.indexOf(q) === -1) {
        sheet.getRange(1, headers.length + 1).setValue(q);
        headers.push(q);
      }
    }

    var rowIndex = findRow(sheet, leadId);
    var isNew    = (rowIndex === -1);
    if (isNew) {
      rowIndex = sheet.getLastRow() + 1;
    }

    // Read the existing row so a later write never blanks an earlier value.
    var row = isNew
      ? new Array(headers.length)
      : sheet.getRange(rowIndex, 1, 1, headers.length).getValues()[0];
    while (row.length < headers.length) {
      row.push('');
    }

    set(row, headers, 'Lead ID',  leadId);
    set(row, headers, 'Status',   String(body.status || (isApply ? 'applied' : 'optin')));
    set(row, headers, 'Tanggal',  dateOnly(body.date));
    set(row, headers, 'Nama',     String(body.name  || ''));
    set(row, headers, 'Email',    String(body.email || ''));
    var wa = waDigits(String(body.phone || ''));
    set(row, headers, 'WhatsApp', wa);

    // Source is the landing URL captured at opt-in — never overwrite it with
    // the application page's URL on the second call.
    if (isNew || !get(row, headers, 'Source')) {
      set(row, headers, 'Source', String(body.source || ''));
    }

    if (isApply) {
      for (var j = 0; j < answers.length; j++) {
        var qq = String((answers[j] && answers[j].q) || '').trim();
        if (qq) {
          set(row, headers, qq, String((answers[j] && answers[j].a) || ''));
        }
      }
    }

    sheet.getRange(rowIndex, 1, 1, headers.length).setValues([row]);

    // Make the number itself the link. Rich text rather than a HYPERLINK()
    // formula: the formula's argument separator is , in some locales and ; in
    // others, so a formula written here breaks on a sheet set to the other one.
    var waCol = headers.indexOf('WhatsApp') + 1;
    if (wa && waCol > 0) {
      sheet.getRange(rowIndex, waCol).setRichTextValue(
        SpreadsheetApp.newRichTextValue()
          .setText(wa)
          .setLinkUrl('https://wa.me/' + wa)
          .build()
      );
    }

    return reply(true, isNew ? 'created' : 'updated');
  } catch (err) {
    return reply(false, String(err));
  } finally {
    lock.releaseLock();
  }
}

/** GET is only for eyeballing which version the deployment serves. */
function doGet() {
  return ContentService
    .createTextOutput(JSON.stringify({
      ok: true,
      message: 'mentorship receiver alive',
      version: VERSION,
      columns: FIXED
    }))
    .setMimeType(ContentService.MimeType.JSON);
}

/**
 * Run this ONCE from the editor (Run menu) after changing the column layout.
 * Deletes the leads tab so the next submission rebuilds it with the current
 * FIXED columns -- the script only ever ADDS missing columns, it never removes
 * stale ones, so an old tab keeps its old headers forever.
 */
function resetSheet() {
  var ss = SHEET_ID
    ? SpreadsheetApp.openById(SHEET_ID)
    : SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(SHEET);
  if (sheet) {
    ss.deleteSheet(sheet);
    Logger.log('Deleted "%s". It will be rebuilt on the next submission.', SHEET);
  } else {
    Logger.log('No "%s" tab to delete.', SHEET);
  }
}

function getSheet() {
  var ss = SHEET_ID
    ? SpreadsheetApp.openById(SHEET_ID)
    : SpreadsheetApp.getActiveSpreadsheet();

  if (!ss) {
    throw new Error(
      'No spreadsheet. Set SHEET_ID to the id from the sheet URL, or create ' +
      'the script from inside the sheet via Extensions > Apps Script.'
    );
  }

  var sheet = ss.getSheetByName(SHEET);
  if (!sheet) {
    sheet = ss.insertSheet(SHEET);
  }
  if (sheet.getLastRow() === 0) {
    sheet.getRange(1, 1, 1, FIXED.length).setValues([FIXED]);
    sheet.setFrozenRows(1);
    sheet.getRange(1, 1, 1, FIXED.length).setFontWeight('bold');
    sheet.hideColumns(1); // Lead ID: needed for matching, not for reading.
  }
  return sheet;
}

function getHeaders(sheet) {
  var width = Math.max(sheet.getLastColumn(), FIXED.length);
  var row   = sheet.getRange(1, 1, 1, width).getValues()[0];
  var out   = [];
  for (var i = 0; i < row.length; i++) {
    out.push(String(row[i] || '').trim());
  }
  // Repair a truncated header row (e.g. someone deleted a fixed column).
  for (var f = 0; f < FIXED.length; f++) {
    if (out.indexOf(FIXED[f]) === -1) {
      out.push(FIXED[f]);
      sheet.getRange(1, out.length).setValue(FIXED[f]);
    }
  }
  return out;
}

/** Row number for this lead_id, or -1. Column A only. */
function findRow(sheet, leadId) {
  var last = sheet.getLastRow();
  if (last < 2) {
    return -1;
  }
  var ids = sheet.getRange(2, 1, last - 1, 1).getValues();
  for (var i = 0; i < ids.length; i++) {
    if (Number(ids[i][0]) === leadId) {
      return i + 2;
    }
  }
  return -1;
}

function set(row, headers, name, value) {
  var i = headers.indexOf(name);
  if (i > -1) {
    row[i] = value;
  }
}

function get(row, headers, name) {
  var i = headers.indexOf(name);
  return i > -1 ? row[i] : '';
}

/**
 * Normalise to international digits: 08xx / +62xx / 62xx all become 62xx.
 * Blank phone gives an empty string.
 */
function waDigits(phone) {
  var d = String(phone).replace(/[^0-9]/g, '');
  if (!d) {
    return '';
  }
  if (d.indexOf('0') === 0) {
    d = '62' + d.substring(1);
  }
  return d;
}

/** Date without the time — "2026-08-27". Falls back to today. */
function dateOnly(raw) {
  var d = raw ? new Date(String(raw).replace(' ', 'T')) : new Date();
  if (isNaN(d.getTime())) {
    d = new Date();
  }
  return Utilities.formatDate(d, TIMEZONE, 'yyyy-MM-dd');
}

function reply(ok, msg) {
  return ContentService
    .createTextOutput(JSON.stringify({ ok: ok, message: msg }))
    .setMimeType(ContentService.MimeType.JSON);
}
