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

// Fixed leading columns. Anything after these is a question column.
var FIXED = [
  'Lead ID',
  'Status',
  'Tanggal Opt-in',
  'Tanggal Aplikasi',
  'Nama',
  'Email',
  'WhatsApp',
  'WA Link',
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

    var stamp = String(body.date || '') || nowStamp();

    set(row, headers, 'Lead ID',  leadId);
    set(row, headers, 'Status',   String(body.status || (isApply ? 'applied' : 'optin')));
    set(row, headers, 'Nama',     String(body.name  || ''));
    set(row, headers, 'Email',    String(body.email || ''));
    set(row, headers, 'WhatsApp', String(body.phone || ''));
    set(row, headers, 'WA Link',  waLink(String(body.phone || '')));

    // Source is the landing URL captured at opt-in — never overwrite it with
    // the application page's URL on the second call.
    if (isNew || !get(row, headers, 'Source')) {
      set(row, headers, 'Source', String(body.source || ''));
    }

    if (isApply) {
      set(row, headers, 'Tanggal Aplikasi', stamp);
      for (var j = 0; j < answers.length; j++) {
        var qq = String((answers[j] && answers[j].q) || '').trim();
        if (qq) {
          set(row, headers, qq, String((answers[j] && answers[j].a) || ''));
        }
      }
    } else {
      set(row, headers, 'Tanggal Opt-in', stamp);
    }

    // An application can arrive without an opt-in row (cookie cleared, direct
    // hit) — backfill the opt-in date so the column is never empty.
    if (!get(row, headers, 'Tanggal Opt-in')) {
      set(row, headers, 'Tanggal Opt-in', stamp);
    }

    sheet.getRange(rowIndex, 1, 1, headers.length).setValues([row]);

    return reply(true, isNew ? 'created' : 'updated');
  } catch (err) {
    return reply(false, String(err));
  } finally {
    lock.releaseLock();
  }
}

/** GET is only for eyeballing that the deployment is live. */
function doGet() {
  return reply(true, 'mentorship receiver alive');
}

function getSheet() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(SHEET);
  if (!sheet) {
    sheet = ss.insertSheet(SHEET);
  }
  if (sheet.getLastRow() === 0) {
    sheet.getRange(1, 1, 1, FIXED.length).setValues([FIXED]);
    sheet.setFrozenRows(1);
    sheet.getRange(1, 1, 1, FIXED.length).setFontWeight('bold');
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

/** 08xx / +62xx / 62xx → wa.me link. Blank phone → blank cell. */
function waLink(phone) {
  var d = String(phone).replace(/[^0-9]/g, '');
  if (!d) {
    return '';
  }
  if (d.indexOf('0') === 0) {
    d = '62' + d.substring(1);
  }
  return 'https://wa.me/' + d;
}

function nowStamp() {
  return Utilities.formatDate(new Date(), 'Asia/Jakarta', 'yyyy-MM-dd HH:mm:ss');
}

function reply(ok, msg) {
  return ContentService
    .createTextOutput(JSON.stringify({ ok: ok, message: msg }))
    .setMimeType(ContentService.MimeType.JSON);
}
