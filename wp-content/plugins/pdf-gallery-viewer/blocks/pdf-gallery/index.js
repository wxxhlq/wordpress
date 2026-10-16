(function (blocks, i18n, element, editor, components) {
  const el = element.createElement;
  const __ = i18n.__;
  const MediaUpload = editor.MediaUpload;
  const MediaUploadCheck = editor.MediaUploadCheck;
  const RichText = editor.RichText;
  const InspectorControls = editor.InspectorControls;
  const { RawHTML } = element;
  const mediaUpload = (wp.blockEditor || wp.editor).mediaUpload;

  blocks.registerBlockType("pdf-gallery/pdf-multi-block", {
    title: __("PDF Gallery (Table View)", "pdf-gallery"),
    icon: "media-document",
    category: "media",
    attributes: {
      galleryId: {
        type: "string",
        default: "",
      },
      items: {
        type: "array",
        default: [],
      },
      layout: {
        type: "string",
        default: "grid",
      },
      ui: {
        type: "boolean",
        default: true,
      },
      accentColor: {
        type: "string",
        default: "",
      },
    },
    edit: function (props) {
      const { items, layout, ui, galleryId, accentColor } = props.attributes;

      // Ensure a stable id for server-side persisted manual ordering (so moving blocks doesn't break mapping).
      if (!galleryId && props.clientId) {
        props.setAttributes({ galleryId: "pgv-" + props.clientId.replace(/[^a-z0-9]/gi, "").slice(0, 12) });
      }

      function updateItem(index, field, value) {
        const newItems = [...items];
        newItems[index][field] = value;
        props.setAttributes({ items: newItems });
      }

      function normalizePositions(list) {
        // Ensure unique incremental positions starting from 1.
        const sorted = [...list].sort((a, b) => (Number(a.position) || 0) - (Number(b.position) || 0));
        return sorted.map((it, i) => ({ ...it, position: i + 1 }));
      }

      function addItem() {
        const nextPos = items.length ? Math.max(...items.map((i) => Number(i.position) || 0)) + 1 : 1;
        props.setAttributes({
          items: [...items, { url: "", title: "", filename: "", position: nextPos }],
        });
      }

      function removeItem(index) {
        const newItems = [...items];
        newItems.splice(index, 1);
        props.setAttributes({ items: newItems });
      }

      function updateLayout(value) {
        props.setAttributes({ layout: value });
      }

      function updateUi(value) {
        props.setAttributes({ ui: value });
      }

      function updateAccentColor(value) {
        props.setAttributes({ accentColor: value || "" });
      }

      const inspectorControls = el(
        InspectorControls,
        { key: "inspector" },
        el(
          components.PanelBody,
          { title: __("Appearance", "pdf-gallery"), initialOpen: true },
          el(components.BaseControl, { label: __("Accent color", "pdf-gallery") },
            el(components.ColorPicker, {
              color: accentColor || "#7db3da",
              onChange: updateAccentColor,
              enableAlpha: false,
            })
          ),
          accentColor
            ? el(
                components.Button,
                { variant: "link", isDestructive: true, onClick: () => updateAccentColor("") },
                __("Use site default", "pdf-gallery")
              )
            : el("p", { className: "components-base-control__help" }, __("Using the site default accent color.", "pdf-gallery"))
        )
      );

      const blockContent = el("div", { className: "pdf-gallery-block", key: "content" }, [
        el("h4", {}, __("PDF Gallery (Tabular View)", "pdf-gallery")),
        el(components.SelectControl, {
          label: "Layout",
          value: layout,
          options: [
            { label: "Grid", value: "grid" },
            { label: "List", value: "list" },
            { label: "Slider", value: "slider" },
          ],
          onChange: updateLayout,
        }),
        el("table", { className: "pdf-table" }, [
          el(
            "thead",
            {},
            el("tr", {}, [
              el("th", {}, "#"),
              el("th", {}, "Title"),
              el("th", {}, "Filename"),
              el("th", {}, "Actions"),
            ])
          ),
          el(
            "tbody",
            {},
            items.map((item, index) =>
              el("tr", { key: index }, [
                el(
                  "td",
                  { style: { width: "70px" } },
                  el(components.TextControl, {
                    type: "number",
                    value: item.position || index + 1,
                    onChange: (value) => updateItem(index, "position", parseInt(value || "0", 10) || 0),
                    min: 1,
                  })
                ),
                el(
                  "td",
                  {},
                  el(RichText, {
                    tagName: "div",
                    value: item.title,
                    placeholder: "Enter title…",
                    onChange: (value) => updateItem(index, "title", value),
                    className: "pdf-table-title",
                  })
                ),
                el(
                  "td",
                  {},
                  el("code", { style: { opacity: 0.8 } }, item.filename || "—")
                ),
                el("td", {}, [
                  el(MediaUpload, {
                    onSelect: (media) => {
                      updateItem(index, "url", media.url);
                      updateItem(index, "filename", (media && media.url ? media.url.split("/").pop() : ""));
                      // If no explicit position, set it to end
                      if (!item.position) {
                        const nextPos = items.length ? Math.max(...items.map((i) => Number(i.position) || 0)) + 1 : 1;
                        updateItem(index, "position", nextPos);
                      }
                    },
                    allowedTypes: ["application/pdf"],
                    render: ({ open }) =>
                      el(
                        components.Button,
                        {
                          onClick: open,
                          variant: "secondary",
                          style: { marginRight: "6px" },
                        },
                        "Change"
                      ),
                  }),
                  el(
                    components.Button,
                    {
                      onClick: () => removeItem(index),
                      isDestructive: true,
                      variant: "link",
                    },
                    "Delete"
                  ),
                ]),
              ])
            )
          ),
        ]),
        el(
          "div",
          { style: { marginTop: "15px", display: "flex", gap: "10px", alignItems: "center" } },
          [
            el(
              components.Button,
              {
                onClick: () => props.setAttributes({ items: normalizePositions(items) }),
                variant: "secondary",
              },
              "Normalize order (#)"
            ),
            el(
              components.Button,
              {
                onClick: addItem,
                isPrimary: true,
              },
              "Add PDF"
            ),
          ]
        ),
      ]);

      return el(element.Fragment, {}, [inspectorControls, blockContent]);
    },
    save: function ({ attributes }) {
      const { items, layout, ui, galleryId, accentColor } = attributes;
      if (!items.length) return null;

      // Sort by numeric position (admin-defined) and then by title as tiebreaker
      const ordered = [...items]
        .filter((item) => item.url)
        .sort((a, b) => {
          const pa = Number(a.position) || 0;
          const pb = Number(b.position) || 0;
          if (pa !== pb) return pa - pb;
          return String(a.title || "").localeCompare(String(b.title || ""));
        });

      const galleryShortcodes = ordered
        .map(
          (item) =>
            `[pdf_gallery url="${item.url}" title="${item.title || ""}"]`
        )
        .join("\n");

      const accentAttr = accentColor ? ` accent_color="${accentColor}"` : "";

      return el(
        "div",
        {},
        `[pdf_gallery_container id="${galleryId || ""}" layout="${layout}" order_by="none" order="asc" ui="${ui ? 1 : 0}"${accentAttr}]\n${galleryShortcodes}\n[/pdf_gallery_container]`
      );
    },
  });
})(
  window.wp.blocks,
  window.wp.i18n,
  window.wp.element,
  window.wp.blockEditor || window.wp.editor,
  window.wp.components
);
