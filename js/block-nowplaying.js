/**
 * js/block-nowplaying.js
 * Gutenberg カスタムブロック: Now Playing ギャラリー
 * カテゴリー: media / widgets
 */
(function (wp) {
  const { registerBlockType } = wp.blocks;
  const { createElement: el } = wp.element;
  const { InspectorControls, useBlockProps } = wp.blockEditor || wp.editor;
  const { PanelBody, RangeControl, ToggleControl } = wp.components;
  const { __ } = wp.i18n;

  registerBlockType('wawahz/nowplaying-gallery', {
    apiVersion: 2,
    supports: { html: false },
    title: __('Now Playing ギャラリー', 'wawahz'),
    description: __('Material 3 Expressive スタイルの音楽・メディアギャラリーを埋め込みます。', 'wawahz'),
    category: 'media',
    icon: 'playlist-audio',
    keywords: [
      __('音楽', 'wawahz'),
      __('now playing', 'wawahz'),
      __('youtube', 'wawahz'),
      __('プレイリスト', 'wawahz'),
    ],
    attributes: {
      limit: {
        type: 'number',
        default: 6,
      },
      showHeader: {
        type: 'boolean',
        default: true,
      },
    },

    edit: function (props) {
      const { attributes, setAttributes } = props;
      const { limit, showHeader } = attributes;
      const blockProps = useBlockProps({ className: 'wawahz-gutenberg-nowplaying-block' });

      return el(
        'div',
        blockProps,
        el(
          InspectorControls,
          null,
          el(
            PanelBody,
            { title: __('ギャラリー設定', 'wawahz'), initialOpen: true },
            el(RangeControl, {
              label: __('表示曲数', 'wawahz'),
              value: limit,
              onChange: (val) => setAttributes({ limit: val }),
              min: 2,
              max: 12,
              step: 1,
            }),
            el(ToggleControl, {
              label: __('ヘッダーを表示する', 'wawahz'),
              checked: showHeader,
              onChange: (val) => setAttributes({ showHeader: val }),
            })
          )
        ),
        // エディタ内プレビュー表示
        el(
          'div',
          {
            style: {
              padding: '20px',
              borderRadius: '24px',
              background: '#1a2223',
              color: '#fff',
              border: '1px solid rgba(255,255,255,0.12)',
            },
          },
          el(
            'div',
            { style: { display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '12px' } },
            el('span', { className: 'dashicons dashicons-playlist-audio', style: { color: '#E8590C', fontSize: '24px' } }),
            el('strong', { style: { fontSize: '18px' } }, '🎧 Now Playing ギャラリー (プレビュー)')
          ),
          el(
            'p',
            { style: { color: 'rgba(255,255,255,0.7)', fontSize: '13px', margin: '0 0 16px' } },
            `Material 3 Expressive カードが最大 ${limit} 曲、投稿の音声・動画・YouTubeプレイヤー付きで表示されます。`
          ),
          el(
            'div',
            {
              style: {
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fill, minmax(220px, 1fr))',
                gap: '12px',
              },
            },
            [1, 2].map((i) =>
              el(
                'div',
                {
                  key: i,
                  style: {
                    background: '#232e2f',
                    borderRadius: '16px',
                    padding: '12px',
                    border: '1px solid rgba(255,255,255,0.08)',
                  },
                },
                el('div', {
                  style: {
                    height: '100px',
                    borderRadius: '12px',
                    background: 'linear-gradient(135deg, #0e3438 0%, #174b50 100%)',
                    marginBottom: '8px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    color: '#E8590C',
                  },
                }, '▶ 音声・動画・YouTube'),
                el('div', { style: { fontWeight: 'bold', fontSize: '14px' } }, i === 1 ? '投稿のメディア' : '投稿のメディア'),
                el('div', { style: { fontSize: '12px', color: '#E8590C' } }, '公開した投稿から自動表示')
              )
            )
          )
        )
      );
    },

    save: function () {
      // サーバーサイドレンダリング (PHP) を使用するため null を返す
      return null;
    },
  });
})(window.wp);
