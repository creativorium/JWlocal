/**
 * Block editor UI for all jwt/* blocks.
 * JSX compiles to wp.element.createElement (see vite.config.js) — the bundle
 * has zero dependencies of its own, everything comes from WordPress globals.
 * Front-end markup lives in each block's render.php; the editor mirrors the
 * same classes so the canvas preview matches the real site.
 */
import './editor.scss';

const { registerBlockType } = wp.blocks;
const {
  useBlockProps,
  RichText,
  InnerBlocks,
  InspectorControls,
  MediaUpload,
  MediaUploadCheck,
} = wp.blockEditor;
const {
  PanelBody,
  TextControl,
  ToggleControl,
  RangeControl,
  SelectControl,
  Button,
} = wp.components;
const { __ } = wp.i18n;
const ServerSideRender = wp.serverSideRender;

// Mirrors jwt_icon() in inc/blocks.php so the editor preview is faithful.
const ICONS = {
  video:
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8" fill="currentColor" stroke="none"/></svg>',
  community:
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
  live:
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="2"/><path d="M16.24 7.76a6 6 0 0 1 0 8.49"/><path d="M7.76 16.24a6 6 0 0 1 0-8.49"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/><path d="M4.93 19.07a10 10 0 0 1 0-14.14"/></svg>',
  chart:
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
  target:
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
  docs:
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
  shield:
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
  spark:
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
};

const ICON_OPTIONS = Object.keys(ICONS).map((k) => ({ label: k, value: k }));

// --- Shared pieces -----------------------------------------------------------

const HeaderEdit = ({ attributes, setAttributes }) => (
  <div className={`jwt-section-header${attributes.center ? ' is-center' : ''}`}>
    <RichText
      tagName="span"
      className="jwt-eyebrow"
      allowedFormats={[]}
      placeholder={__('Eyebrow…', 'jwtrading')}
      value={attributes.eyebrow}
      onChange={(eyebrow) => setAttributes({ eyebrow })}
    />
    <RichText
      tagName="h2"
      className="jwt-title"
      placeholder={__('Judul section…', 'jwtrading')}
      value={attributes.title}
      onChange={(title) => setAttributes({ title })}
    />
    <RichText
      tagName="p"
      className="jwt-lead"
      placeholder={__('Deskripsi singkat…', 'jwtrading')}
      value={attributes.lead}
      onChange={(lead) => setAttributes({ lead })}
    />
  </div>
);

const HeaderPanel = ({ attributes, setAttributes, children }) => (
  <InspectorControls>
    <PanelBody title={__('Pengaturan Section', 'jwtrading')}>
      <ToggleControl
        label={__('Header rata tengah', 'jwtrading')}
        checked={!!attributes.center}
        onChange={(center) => setAttributes({ center })}
      />
      {children}
    </PanelBody>
  </InspectorControls>
);

const makeSectionEdit =
  ({ className, innerClass, allowed, template, panelExtras }) =>
  (props) => {
    const { attributes, setAttributes } = props;
    const blockProps = useBlockProps({ className });

    return (
      <>
        <HeaderPanel attributes={attributes} setAttributes={setAttributes}>
          {panelExtras ? panelExtras(props) : null}
        </HeaderPanel>
        <section {...blockProps}>
          <div className="jwt-container">
            <HeaderEdit attributes={attributes} setAttributes={setAttributes} />
            <div className={innerClass}>
              <InnerBlocks allowedBlocks={allowed} template={template} />
            </div>
          </div>
        </section>
      </>
    );
  };

const saveInner = () => <InnerBlocks.Content />;
const saveNull = () => null;

// --- Hero ---------------------------------------------------------------------

registerBlockType('jwt/hero', {
  edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps({ className: 'jwt-hero' });

    return (
      <>
        <InspectorControls>
          <PanelBody title={__('Badge Rating (Trustpilot, dll)', 'jwtrading')} initialOpen={true}>
            <p style={{ fontSize: 12, opacity: 0.8 }}>
              {__('Kalau diisi, badge ini menggantikan Eyebrow biasa di bawah — cocok untuk rating/review.', 'jwtrading')}
            </p>
            <TextControl
              label={__('Teks tebal (mis. Excellent)', 'jwtrading')}
              value={attributes.ratingText}
              onChange={(ratingText) => setAttributes({ ratingText })}
            />
            <TextControl
              label={__('Nilai rating (mis. 4.4 out of 5)', 'jwtrading')}
              value={attributes.ratingValue}
              onChange={(ratingValue) => setAttributes({ ratingValue })}
            />
            <TextControl
              label={__('Nama brand (mis. Trustpilot)', 'jwtrading')}
              value={attributes.ratingBrand}
              onChange={(ratingBrand) => setAttributes({ ratingBrand })}
            />
            <TextControl
              label={__('URL (opsional — link ke halaman review)', 'jwtrading')}
              value={attributes.ratingUrl}
              onChange={(ratingUrl) => setAttributes({ ratingUrl })}
            />
          </PanelBody>

          <PanelBody title={__('Tombol & Opsi', 'jwtrading')}>
            <SelectControl
              label={__('Tag judul', 'jwtrading')}
              value={attributes.titleTag}
              options={[
                { label: 'H1 (beranda)', value: 'h1' },
                { label: 'H2', value: 'h2' },
                { label: 'P', value: 'p' },
              ]}
              onChange={(titleTag) => setAttributes({ titleTag })}
            />
            <p style={{ fontSize: 12, opacity: 0.8 }}>
              {__('Teks tombol diedit langsung di tombolnya (klik tombol di preview). URL diatur di sini.', 'jwtrading')}
            </p>
            <TextControl
              label={__('URL tombol utama', 'jwtrading')}
              value={attributes.primaryUrl}
              onChange={(primaryUrl) => setAttributes({ primaryUrl })}
            />
            <TextControl
              label={__('URL tombol kedua', 'jwtrading')}
              value={attributes.secondaryUrl}
              onChange={(secondaryUrl) => setAttributes({ secondaryUrl })}
            />
            <TextControl
              label={__('Chips', 'jwtrading')}
              help={__('Pisahkan dengan • — contoh: Fokus • Disiplin • Tenang', 'jwtrading')}
              value={attributes.chips}
              onChange={(chips) => setAttributes({ chips })}
            />
          </PanelBody>
        </InspectorControls>

        <section {...blockProps}>
          <div className="jwt-container">
            {attributes.ratingText ? (
              <span className="jwt-hero__rating">
                <strong>{attributes.ratingText}</strong>
                {attributes.ratingValue ? <span>{attributes.ratingValue}</span> : null}
                <svg className="jwt-hero__rating-star" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                  <path d="M10 1.5l2.47 5.53 6.03.58-4.55 4.03 1.33 5.9L10 14.62l-5.28 2.92 1.33-5.9L1.5 7.61l6.03-.58L10 1.5z" />
                </svg>
                {attributes.ratingBrand ? (
                  <>
                    <span className="jwt-hero__rating-sep">|</span>
                    <span>{attributes.ratingBrand}</span>
                  </>
                ) : null}
              </span>
            ) : (
              <RichText
                tagName="span"
                className="jwt-eyebrow"
                allowedFormats={[]}
                placeholder={__('Eyebrow / promo…', 'jwtrading')}
                value={attributes.eyebrow}
                onChange={(eyebrow) => setAttributes({ eyebrow })}
              />
            )}
            <RichText
              tagName={attributes.titleTag || 'h1'}
              className="jwt-hero__title"
              placeholder={__('Judul besar…', 'jwtrading')}
              value={attributes.title}
              onChange={(title) => setAttributes({ title })}
            />
            <RichText
              tagName="p"
              className="jwt-hero__lead"
              placeholder={__('Kalimat pendukung…', 'jwtrading')}
              value={attributes.lead}
              onChange={(lead) => setAttributes({ lead })}
            />
            <div className="jwt-hero__actions">
              <RichText
                tagName="span"
                className="jwt-btn jwt-btn--primary"
                allowedFormats={[]}
                placeholder={__('Teks tombol utama…', 'jwtrading')}
                value={attributes.primaryText}
                onChange={(primaryText) => setAttributes({ primaryText })}
              />
              <RichText
                tagName="span"
                className="jwt-btn jwt-btn--ghost"
                allowedFormats={[]}
                placeholder={__('Teks tombol kedua…', 'jwtrading')}
                value={attributes.secondaryText}
                onChange={(secondaryText) => setAttributes({ secondaryText })}
              />
            </div>
            <RichText
              tagName="p"
              className="jwt-hero__note"
              allowedFormats={[]}
              placeholder={__('Catatan kecil (opsional)…', 'jwtrading')}
              value={attributes.note}
              onChange={(note) => setAttributes({ note })}
            />
            {attributes.chips ? (
              <div className="jwt-hero__chips">
                {attributes.chips
                  .split(/[•|]/)
                  .map((c) => c.trim())
                  .filter(Boolean)
                  .map((chip, i) => (
                    <span key={i} className="jwt-pill">
                      {chip}
                    </span>
                  ))}
              </div>
            ) : null}
          </div>
        </section>
      </>
    );
  },
  save: saveNull,
});

// --- Stats ----------------------------------------------------------------------

registerBlockType('jwt/stats', {
  edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps({ className: 'jwt-stats' });
    return (
      <>
        <HeaderPanel attributes={attributes} setAttributes={setAttributes}>
          <TextControl
            label={__('Teks tombol (opsional)', 'jwtrading')}
            value={attributes.buttonText}
            onChange={(buttonText) => setAttributes({ buttonText })}
          />
          <TextControl
            label={__('URL tombol', 'jwtrading')}
            value={attributes.buttonUrl}
            onChange={(buttonUrl) => setAttributes({ buttonUrl })}
          />
        </HeaderPanel>
        <section {...blockProps}>
          <div className="jwt-container">
            <HeaderEdit attributes={attributes} setAttributes={setAttributes} />
            <div className="jwt-stats__grid">
              <InnerBlocks
                allowedBlocks={['jwt/stat-item']}
                template={[
                  ['jwt/stat-item'],
                  ['jwt/stat-item'],
                  ['jwt/stat-item'],
                  ['jwt/stat-item'],
                ]}
              />
            </div>
            {attributes.buttonText ? (
              <div className="jwt-stats__cta">
                <span className="jwt-btn jwt-btn--ghost">{attributes.buttonText}</span>
              </div>
            ) : null}
          </div>
        </section>
      </>
    );
  },
  save: saveInner,
});

registerBlockType('jwt/stat-item', {
  edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps({ className: 'jwt-card jwt-stat' });

    return (
      <>
        <InspectorControls>
          <PanelBody title={__('Animasi Angka', 'jwtrading')}>
            <TextControl
              label={__('Angka target (opsional)', 'jwtrading')}
              help={__('Hanya angka, contoh: 15000. Dianimasikan saat terlihat.', 'jwtrading')}
              value={attributes.count}
              onChange={(count) => setAttributes({ count })}
            />
            <TextControl
              label={__('Suffix', 'jwtrading')}
              help={__('Contoh: +', 'jwtrading')}
              value={attributes.suffix}
              onChange={(suffix) => setAttributes({ suffix })}
            />
          </PanelBody>
        </InspectorControls>
        <div {...blockProps}>
          <RichText
            tagName="span"
            className="jwt-stat__number"
            allowedFormats={[]}
            placeholder="15.000+"
            value={attributes.value}
            onChange={(value) => setAttributes({ value })}
          />
          <RichText
            tagName="span"
            className="jwt-stat__label"
            allowedFormats={[]}
            placeholder={__('Label…', 'jwtrading')}
            value={attributes.label}
            onChange={(label) => setAttributes({ label })}
          />
        </div>
      </>
    );
  },
  save: saveNull,
});

// --- Features ---------------------------------------------------------------------

registerBlockType('jwt/features', {
  edit: makeSectionEdit({
    className: 'jwt-features',
    innerClass: 'jwt-features__grid',
    allowed: ['jwt/feature-item'],
    template: [
      ['jwt/feature-item', { icon: 'video' }],
      ['jwt/feature-item', { icon: 'community' }],
      ['jwt/feature-item', { icon: 'live' }],
    ],
  }),
  save: saveInner,
});

registerBlockType('jwt/feature-item', {
  edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps({ className: 'jwt-card jwt-feature' });

    return (
      <>
        <InspectorControls>
          <PanelBody title={__('Ikon / Nomor', 'jwtrading')}>
            <TextControl
              label={__('Nomor (opsional)', 'jwtrading')}
              help={__('Contoh: 01 — kartu berubah jadi gaya pillar bernomor.', 'jwtrading')}
              value={attributes.number}
              onChange={(number) => setAttributes({ number })}
            />
            <SelectControl
              label={__('Pilih ikon', 'jwtrading')}
              value={attributes.icon}
              options={ICON_OPTIONS}
              onChange={(icon) => setAttributes({ icon })}
            />
          </PanelBody>
        </InspectorControls>
        <article {...blockProps}>
          {attributes.number ? (
            <div className="jwt-feature__num">{attributes.number}</div>
          ) : (
          <span
            className="jwt-feature__icon"
            dangerouslySetInnerHTML={{ __html: ICONS[attributes.icon] || ICONS.spark }}
          />
          )}
          <RichText
            tagName="h3"
            className="jwt-feature__title"
            allowedFormats={[]}
            placeholder={__('Judul fitur…', 'jwtrading')}
            value={attributes.title}
            onChange={(title) => setAttributes({ title })}
          />
          <RichText
            tagName="p"
            className="jwt-feature__text"
            placeholder={__('Deskripsi singkat…', 'jwtrading')}
            value={attributes.text}
            onChange={(text) => setAttributes({ text })}
          />
        </article>
      </>
    );
  },
  save: saveNull,
});

// --- Curriculum ----------------------------------------------------------------------

registerBlockType('jwt/curriculum', {
  edit: makeSectionEdit({
    className: 'jwt-curriculum',
    innerClass: 'jwt-curriculum__list',
    allowed: ['jwt/curriculum-item'],
    template: [['jwt/curriculum-item'], ['jwt/curriculum-item'], ['jwt/curriculum-item']],
  }),
  save: saveInner,
});

registerBlockType('jwt/curriculum-item', {
  edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps({ className: 'jwt-curriculum-item' });

    return (
      <div {...blockProps}>
        <InspectorControls>
          <PanelBody title={__('Nomor', 'jwtrading')}>
            <TextControl
              label={__('Nomor manual (opsional)', 'jwtrading')}
              help={__('Kosongkan untuk penomoran otomatis. Diisi mis. 04 untuk baris modul di panel Program.', 'jwtrading')}
              value={attributes.number}
              onChange={(number) => setAttributes({ number })}
            />
          </PanelBody>
        </InspectorControls>
        <div>
          <RichText
            tagName="h3"
            className="jwt-curriculum-item__title"
            allowedFormats={[]}
            placeholder={__('Nama modul…', 'jwtrading')}
            value={attributes.title}
            onChange={(title) => setAttributes({ title })}
          />
          <RichText
            tagName="p"
            className="jwt-curriculum-item__text"
            placeholder={__('Apa yang dipelajari…', 'jwtrading')}
            value={attributes.text}
            onChange={(text) => setAttributes({ text })}
          />
        </div>
        <RichText
          tagName="span"
          className="jwt-pill"
          allowedFormats={[]}
          placeholder={__('Tag (opsional)', 'jwtrading')}
          value={attributes.tag}
          onChange={(tag) => setAttributes({ tag })}
        />
      </div>
    );
  },
  save: saveNull,
});

// --- Testimonials ---------------------------------------------------------------------

registerBlockType('jwt/testimonials', {
  edit: makeSectionEdit({
    className: 'jwt-testimonials',
    innerClass: 'jwt-testimonials__track',
    allowed: ['jwt/testimonial-item'],
    template: [['jwt/testimonial-item'], ['jwt/testimonial-item'], ['jwt/testimonial-item']],
  }),
  save: saveInner,
});

registerBlockType('jwt/testimonial-item', {
  edit({ attributes, setAttributes }) {
    const { imageId, imageUrl } = attributes;
    const blockProps = useBlockProps({
      className: `jwt-card jwt-testimonial${imageId ? ' jwt-testimonial--image' : ''}`,
    });

    const onSelectImage = (media) =>
      setAttributes({ imageId: media.id, imageUrl: media.url, imageAlt: media.alt || '' });
    const onRemoveImage = () => setAttributes({ imageId: 0, imageUrl: '', imageAlt: '' });

    return (
      <>
        <InspectorControls>
          <PanelBody title={__('Screenshot (opsional)', 'jwtrading')}>
            <p style={{ fontSize: 12, opacity: 0.8 }}>
              {__('Kalau gambar dipilih, kartu menampilkan screenshot; kalau kosong, kartu kutipan teks.', 'jwtrading')}
            </p>
            <MediaUploadCheck>
              <MediaUpload
                onSelect={onSelectImage}
                allowedTypes={['image']}
                value={imageId}
                render={({ open }) => (
                  <div style={{ display: 'flex', gap: 8 }}>
                    <Button variant="secondary" onClick={open}>
                      {imageId ? __('Ganti gambar', 'jwtrading') : __('Pilih gambar', 'jwtrading')}
                    </Button>
                    {imageId ? (
                      <Button variant="link" isDestructive onClick={onRemoveImage}>
                        {__('Hapus', 'jwtrading')}
                      </Button>
                    ) : null}
                  </div>
                )}
              />
            </MediaUploadCheck>
          </PanelBody>
        </InspectorControls>

        <figure {...blockProps}>
          {imageId ? (
            <img src={imageUrl} alt={attributes.imageAlt} />
          ) : (
            <>
              <RichText
                tagName="blockquote"
                className="jwt-testimonial__quote"
                placeholder={__('Kutipan testimoni…', 'jwtrading')}
                value={attributes.quote}
                onChange={(quote) => setAttributes({ quote })}
              />
              <figcaption className="jwt-testimonial__who">
                <RichText
                  tagName="span"
                  className="jwt-testimonial__name"
                  allowedFormats={[]}
                  placeholder={__('Nama', 'jwtrading')}
                  value={attributes.name}
                  onChange={(name) => setAttributes({ name })}
                />
                <RichText
                  tagName="span"
                  className="jwt-testimonial__role"
                  allowedFormats={[]}
                  placeholder={__('Peran — mis. Member Bootcamp', 'jwtrading')}
                  value={attributes.role}
                  onChange={(role) => setAttributes({ role })}
                />
              </figcaption>
            </>
          )}
        </figure>
      </>
    );
  },
  save: saveNull,
});

// --- FAQ --------------------------------------------------------------------------

registerBlockType('jwt/faq', {
  edit: makeSectionEdit({
    className: 'jwt-faq',
    innerClass: 'jwt-faq__list',
    allowed: ['jwt/faq-item'],
    template: [['jwt/faq-item'], ['jwt/faq-item'], ['jwt/faq-item']],
    panelExtras: ({ attributes, setAttributes }) => (
      <>
        <ToggleControl
          label={__('Skema FAQ untuk Google (SEO)', 'jwtrading')}
          checked={!!attributes.schema}
          onChange={(schema) => setAttributes({ schema })}
        />
        <TextControl
          label={__('Teks tombol di bawah (opsional)', 'jwtrading')}
          value={attributes.buttonText}
          onChange={(buttonText) => setAttributes({ buttonText })}
        />
        <TextControl
          label={__('URL tombol', 'jwtrading')}
          value={attributes.buttonUrl}
          onChange={(buttonUrl) => setAttributes({ buttonUrl })}
        />
      </>
    ),
  }),
  save: saveInner,
});

registerBlockType('jwt/faq-item', {
  edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps({ className: 'jwt-faq-item', style: { padding: 0 } });

    return (
      <div {...blockProps}>
        <RichText
          tagName="div"
          className="jwt-faq-item__q"
          allowedFormats={[]}
          placeholder={__('Pertanyaan…', 'jwtrading')}
          value={attributes.question}
          onChange={(question) => setAttributes({ question })}
        />
        <RichText
          tagName="div"
          className="jwt-faq-item__answer"
          placeholder={__('Jawaban…', 'jwtrading')}
          value={attributes.answer}
          onChange={(answer) => setAttributes({ answer })}
        />
      </div>
    );
  },
  save: saveNull,
});

// --- CTA --------------------------------------------------------------------------

registerBlockType('jwt/cta', {
  edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps({ className: 'jwt-cta' });

    return (
      <>
        <HeaderPanel attributes={attributes} setAttributes={setAttributes}>
          <TextControl
            label={__('URL tombol', 'jwtrading')}
            value={attributes.buttonUrl}
            onChange={(buttonUrl) => setAttributes({ buttonUrl })}
          />
        </HeaderPanel>

        <section {...blockProps}>
          <div className="jwt-container">
            <div className="jwt-cta__box">
              <HeaderEdit attributes={attributes} setAttributes={setAttributes} />
              <div className="jwt-cta__actions">
                <RichText
                  tagName="span"
                  className="jwt-btn jwt-btn--primary"
                  allowedFormats={[]}
                  placeholder={__('Teks tombol…', 'jwtrading')}
                  value={attributes.buttonText}
                  onChange={(buttonText) => setAttributes({ buttonText })}
                />
              </div>
              <div className="jwt-cta__promo">
                <RichText
                  tagName="span"
                  className="jwt-pill"
                  allowedFormats={[]}
                  placeholder={__('Promo (opsional) — mis. Kode KG29RRJB', 'jwtrading')}
                  value={attributes.promoText}
                  onChange={(promoText) => setAttributes({ promoText })}
                />
              </div>
              <RichText
                tagName="p"
                className="jwt-cta__note"
                allowedFormats={[]}
                placeholder={__('Catatan kecil (opsional)…', 'jwtrading')}
                value={attributes.note}
                onChange={(note) => setAttributes({ note })}
              />
            </div>
          </div>
        </section>
      </>
    );
  },
  save: saveNull,
});

// --- Course grid ---------------------------------------------------------------------

registerBlockType('jwt/course-grid', {
  edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps({ className: 'jwt-courses' });

    return (
      <>
        <HeaderPanel attributes={attributes} setAttributes={setAttributes}>
          <RangeControl
            label={__('Jumlah produk', 'jwtrading')}
            min={1}
            max={12}
            value={attributes.count}
            onChange={(count) => setAttributes({ count })}
          />
          <RangeControl
            label={__('Kolom', 'jwtrading')}
            min={1}
            max={4}
            value={attributes.columns}
            onChange={(columns) => setAttributes({ columns })}
          />
          <TextControl
            label={__('Kategori produk (slug, opsional)', 'jwtrading')}
            value={attributes.category}
            onChange={(category) => setAttributes({ category })}
          />
          <TextControl
            label={__('Teks tombol', 'jwtrading')}
            value={attributes.buttonText}
            onChange={(buttonText) => setAttributes({ buttonText })}
          />
        </HeaderPanel>

        <section {...blockProps}>
          <div className="jwt-container">
            <HeaderEdit attributes={attributes} setAttributes={setAttributes} />
            <ServerSideRender
              block="jwt/course-grid"
              httpMethod="POST"
              attributes={{ ...attributes, eyebrow: '', title: '', lead: '' }}
            />
          </div>
        </section>
      </>
    );
  },
  save: saveNull,
});

// --- Section heading (JW Home.dc) -----------------------------------------------

registerBlockType('jwt/section-heading', {
  edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps({ className: 'jwt-section-heading' });
    return (
      <>
        <HeaderPanel attributes={attributes} setAttributes={setAttributes} />
        <section {...blockProps}>
          <div className="jwt-container">
            <HeaderEdit attributes={attributes} setAttributes={setAttributes} />
          </div>
        </section>
      </>
    );
  },
  save: saveNull,
});

// --- Media frame --------------------------------------------------------------------

registerBlockType('jwt/media-frame', {
  edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps({ className: 'jwt-media-frame' });
    return (
      <>
        <InspectorControls>
          <PanelBody title={__('Label Frame', 'jwtrading')}>
            <TextControl
              label={__('Label kiri (mono)', 'jwtrading')}
              value={attributes.labelLeft}
              onChange={(labelLeft) => setAttributes({ labelLeft })}
            />
            <TextControl
              label={__('Label kanan (hijau)', 'jwtrading')}
              value={attributes.labelRight}
              onChange={(labelRight) => setAttributes({ labelRight })}
            />
          </PanelBody>
        </InspectorControls>
        <section {...blockProps}>
          <div className="jwt-container jwt-media-frame__wrap">
            <div className="jwt-media-frame__box">
              <div className="jwt-media-frame__bar">
                <div className="jwt-media-frame__dots"><span></span><span></span><span></span></div>
                <div className="jwt-media-frame__label">{attributes.labelLeft}</div>
                <div className="jwt-media-frame__label is-green">{attributes.labelRight}</div>
              </div>
              <div className="jwt-media-frame__body">
                <InnerBlocks
                  allowedBlocks={['core/embed', 'core/video', 'core/image', 'core/html']}
                />
              </div>
            </div>
          </div>
        </section>
      </>
    );
  },
  save: saveInner,
});

// --- Statement ------------------------------------------------------------------------

registerBlockType('jwt/statement', {
  edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps({ className: 'jwt-statement' });
    return (
      <section {...blockProps}>
        <div className="jwt-container">
          <div className="jwt-statement__box">
            <RichText
              tagName="span"
              className="jwt-eyebrow"
              allowedFormats={[]}
              placeholder={__('Eyebrow…', 'jwtrading')}
              value={attributes.eyebrow}
              onChange={(eyebrow) => setAttributes({ eyebrow })}
            />
            <RichText
              tagName="h2"
              className="jwt-statement__title"
              placeholder={__('Pernyataan besar…', 'jwtrading')}
              value={attributes.title}
              onChange={(title) => setAttributes({ title })}
            />
            <RichText
              tagName="p"
              className="jwt-statement__lead"
              placeholder={__('Kalimat pendukung…', 'jwtrading')}
              value={attributes.lead}
              onChange={(lead) => setAttributes({ lead })}
            />
          </div>
        </div>
      </section>
    );
  },
  save: saveNull,
});

// --- Product spotlight ---------------------------------------------------------------

registerBlockType('jwt/spotlight', {
  edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps({
      className: `jwt-spotlight${attributes.reverse ? ' is-reverse' : ''}`,
    });

    return (
      <>
        <InspectorControls>
          <PanelBody title={__('Spotlight', 'jwtrading')}>
            <TextControl
              label={__('Chips (pisahkan dengan |)', 'jwtrading')}
              value={attributes.chips}
              onChange={(chips) => setAttributes({ chips })}
            />
            <TextControl
              label={__('URL tombol', 'jwtrading')}
              value={attributes.buttonUrl}
              onChange={(buttonUrl) => setAttributes({ buttonUrl })}
            />
            <ToggleControl
              label={__('Balik posisi cover', 'jwtrading')}
              checked={!!attributes.reverse}
              onChange={(reverse) => setAttributes({ reverse })}
            />
            <MediaUploadCheck>
              <MediaUpload
                onSelect={(media) => setAttributes({ imageId: media.id })}
                allowedTypes={['image']}
                value={attributes.imageId}
                render={({ open }) => (
                  <div style={{ display: 'flex', gap: 8 }}>
                    <Button variant="secondary" onClick={open}>
                      {attributes.imageId ? __('Ganti gambar cover', 'jwtrading') : __('Pakai gambar asli (opsional)', 'jwtrading')}
                    </Button>
                    {attributes.imageId ? (
                      <Button variant="link" isDestructive onClick={() => setAttributes({ imageId: 0 })}>
                        {__('Hapus', 'jwtrading')}
                      </Button>
                    ) : null}
                  </div>
                )}
              />
            </MediaUploadCheck>
          </PanelBody>
        </InspectorControls>

        <section {...blockProps}>
          <div className="jwt-container">
            <div className="jwt-spotlight__panel">
              <div className="jwt-spotlight__media">
                <div className="jwt-spotlight__cover">
                  <div className="jwt-spotlight__cover-glow"></div>
                  <div className="jwt-spotlight__cover-top">
                    <RichText
                      tagName="div"
                      className="jwt-spotlight__cover-label"
                      allowedFormats={[]}
                      placeholder={__('Label cover…', 'jwtrading')}
                      value={attributes.coverLabel}
                      onChange={(coverLabel) => setAttributes({ coverLabel })}
                    />
                  </div>
                  <RichText
                    tagName="div"
                    className="jwt-spotlight__cover-title"
                    allowedFormats={[]}
                    placeholder={__('Judul cover…', 'jwtrading')}
                    value={attributes.coverTitle}
                    onChange={(coverTitle) => setAttributes({ coverTitle })}
                  />
                </div>
              </div>
              <div className="jwt-spotlight__body">
                <RichText
                  tagName="span"
                  className="jwt-badge"
                  allowedFormats={[]}
                  placeholder={__('Badge…', 'jwtrading')}
                  value={attributes.badge}
                  onChange={(badge) => setAttributes({ badge })}
                />
                <RichText
                  tagName="h3"
                  className="jwt-spotlight__title"
                  placeholder={__('Judul…', 'jwtrading')}
                  value={attributes.title}
                  onChange={(title) => setAttributes({ title })}
                />
                <RichText
                  tagName="p"
                  className="jwt-spotlight__text"
                  placeholder={__('Deskripsi…', 'jwtrading')}
                  value={attributes.text}
                  onChange={(text) => setAttributes({ text })}
                />
                <RichText
                  tagName="span"
                  className="jwt-btn jwt-btn--primary"
                  allowedFormats={[]}
                  placeholder={__('Teks tombol…', 'jwtrading')}
                  value={attributes.buttonText}
                  onChange={(buttonText) => setAttributes({ buttonText })}
                />
              </div>
            </div>
          </div>
        </section>
      </>
    );
  },
  save: saveNull,
});

// --- Connector -------------------------------------------------------------------------

registerBlockType('jwt/connector', {
  edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps({ className: 'jwt-connector' });
    return (
      <div {...blockProps}>
        <div className="jwt-container jwt-connector__wrap">
          <RichText
            tagName="span"
            className="jwt-connector__pill"
            allowedFormats={[]}
            placeholder={__('Teks penghubung…', 'jwtrading')}
            value={attributes.text}
            onChange={(text) => setAttributes({ text })}
          />
        </div>
      </div>
    );
  },
  save: saveNull,
});

// --- Program panel ---------------------------------------------------------------------

registerBlockType('jwt/program', {
  edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps({ className: 'jwt-program' });
    return (
      <>
        <InspectorControls>
          <PanelBody title={__('Program', 'jwtrading')}>
            <TextControl
              label={__('URL tombol', 'jwtrading')}
              value={attributes.buttonUrl}
              onChange={(buttonUrl) => setAttributes({ buttonUrl })}
            />
            <TextControl
              label={__('Footnote (mono)', 'jwtrading')}
              value={attributes.footnote}
              onChange={(footnote) => setAttributes({ footnote })}
            />
          </PanelBody>
        </InspectorControls>
        <section {...blockProps}>
          <div className="jwt-container">
            <div className="jwt-program__panel">
              <div className="jwt-program__pitch">
                <RichText
                  tagName="span"
                  className="jwt-eyebrow"
                  allowedFormats={[]}
                  placeholder={__('Eyebrow…', 'jwtrading')}
                  value={attributes.eyebrow}
                  onChange={(eyebrow) => setAttributes({ eyebrow })}
                />
                <RichText
                  tagName="h2"
                  className="jwt-program__title"
                  placeholder={__('Judul…', 'jwtrading')}
                  value={attributes.title}
                  onChange={(title) => setAttributes({ title })}
                />
                <RichText
                  tagName="p"
                  className="jwt-program__lead"
                  placeholder={__('Deskripsi…', 'jwtrading')}
                  value={attributes.lead}
                  onChange={(lead) => setAttributes({ lead })}
                />
                <RichText
                  tagName="span"
                  className="jwt-btn jwt-btn--primary"
                  allowedFormats={[]}
                  placeholder={__('Teks tombol…', 'jwtrading')}
                  value={attributes.buttonText}
                  onChange={(buttonText) => setAttributes({ buttonText })}
                />
              </div>
              <div className="jwt-program__modules">
                <InnerBlocks
                  allowedBlocks={['jwt/curriculum-item']}
                  template={[
                    ['jwt/curriculum-item', { number: '01' }],
                    ['jwt/curriculum-item', { number: '02' }],
                    ['jwt/curriculum-item', { number: '03' }],
                    ['jwt/curriculum-item', { number: '04' }],
                  ]}
                />
                {attributes.footnote ? (
                  <div className="jwt-program__footnote">{attributes.footnote}</div>
                ) : null}
              </div>
            </div>
          </div>
        </section>
      </>
    );
  },
  save: saveInner,
});

// --- Duo CTA ---------------------------------------------------------------------------

registerBlockType('jwt/duo-cta', {
  edit() {
    const blockProps = useBlockProps({ className: 'jwt-duo-cta' });
    return (
      <section {...blockProps}>
        <div className="jwt-container">
          <div className="jwt-duo-cta__grid">
            <InnerBlocks
              allowedBlocks={['jwt/cta-card']}
              template={[
                ['jwt/cta-card', { accent: true }],
                ['jwt/cta-card'],
              ]}
            />
          </div>
        </div>
      </section>
    );
  },
  save: saveInner,
});

registerBlockType('jwt/cta-card', {
  edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps({
      className: `jwt-cta-card${attributes.accent ? ' is-accent' : ''}`,
    });

    return (
      <>
        <InspectorControls>
          <PanelBody title={__('Kartu CTA', 'jwtrading')}>
            <ToggleControl
              label={__('Varian accent (ungu)', 'jwtrading')}
              checked={!!attributes.accent}
              onChange={(accent) => setAttributes({ accent })}
            />
            <TextControl
              label={__('URL tombol', 'jwtrading')}
              value={attributes.buttonUrl}
              onChange={(buttonUrl) => setAttributes({ buttonUrl })}
            />
          </PanelBody>
        </InspectorControls>
        <div {...blockProps}>
          <RichText
            tagName="span"
            className={`jwt-eyebrow${attributes.accent ? '' : ' is-muted'}`}
            allowedFormats={[]}
            placeholder={__('Eyebrow…', 'jwtrading')}
            value={attributes.eyebrow}
            onChange={(eyebrow) => setAttributes({ eyebrow })}
          />
          <RichText
            tagName="h3"
            className="jwt-cta-card__title"
            placeholder={__('Judul…', 'jwtrading')}
            value={attributes.title}
            onChange={(title) => setAttributes({ title })}
          />
          <RichText
            tagName="p"
            className="jwt-cta-card__text"
            placeholder={__('Teks…', 'jwtrading')}
            value={attributes.text}
            onChange={(text) => setAttributes({ text })}
          />
          <RichText
            tagName="span"
            className={`jwt-btn ${attributes.accent ? 'jwt-btn--primary' : 'jwt-btn--ghost'}`}
            allowedFormats={[]}
            placeholder={__('Teks tombol…', 'jwtrading')}
            value={attributes.buttonText}
            onChange={(buttonText) => setAttributes({ buttonText })}
          />
        </div>
      </>
    );
  },
  save: saveNull,
});
