/**
 * CMS หลังบ้าน — ความสะดวกที่เพิ่มบนฟอร์มที่ใช้งานได้อยู่แล้วโดยไม่มี JS (design.md 5.11)
 *
 *   · ตัวนับตัวอักษร + ตัวอย่างผลค้นหา Google (data-counter / data-snippet)
 *   · block builder: เลื่อนขึ้น/ลง · เพิ่มบล็อกจาก <template> · ซ่อนช่องลำดับ/ติ๊กลบ แล้วใช้ปุ่มแทน
 *   · รายการซ้ำได้ (data-rows): ปุ่มเพิ่ม/ลบแถว
 *   · ตัวแก้ HTML ของบทความ (data-html-editor): แถบเครื่องมือ h2 h3 p ul ol strong em ลิงก์ ตาราง รูป
 *
 * ‼️ ข้อความทั้งหมดอ่านจาก #cms-lang (มาจาก lang/*.php) — ไม่มีข้อความตายตัวในไฟล์นี้
 * ‼️ ไม่มี innerHTML กับค่าที่ผู้ใช้พิมพ์ — สร้าง element ด้วย DOM API เสมอ
 */
(function () {
  'use strict';

  var langEl = document.getElementById('cms-lang');
  var t = function (key) { return langEl ? (langEl.dataset[key] || '') : ''; };

  function button(label, icon, onClick) {
    var b = document.createElement('button');
    b.type = 'button';
    b.className = 'btn btn-icon';
    b.setAttribute('aria-label', label);
    b.title = label;
    b.textContent = icon;
    b.addEventListener('click', onClick);
    return b;
  }

  // ------------------------------------------------------------ ตัวนับ + snippet

  function updateCounter(input) {
    var max = parseInt(input.dataset.counter, 10);
    var out = document.getElementById(input.id + '-count');
    var n = Array.from(input.value).length;
    if (out) {
      out.textContent = (t('chars') || ':n / ~:max').replace(':n', n).replace(':max', max);
      out.dataset.over = n > max ? 'true' : 'false';
    }
    var target = input.dataset.snippet;
    var preview = document.querySelector('[data-snippet-preview]');
    if (!preview || !target) { return; }
    if (target === 'title') {
      var title = preview.querySelector('[data-snippet-title]');
      title.textContent = input.value.trim() || title.dataset.fallback || '';
    } else if (target === 'desc') {
      preview.querySelector('[data-snippet-desc]').textContent = input.value.trim();
    }
  }

  document.querySelectorAll('[data-counter]').forEach(function (input) {
    input.addEventListener('input', function () { updateCounter(input); });
    updateCounter(input);
  });

  // ------------------------------------------------------------ รายการซ้ำได้

  var uid = Date.now();

  function wireRow(row) {
    if (row.querySelector(':scope > [data-row-remove]')) { return; }
    var remove = button(t('removeRow'), '×', function () { row.remove(); });
    remove.setAttribute('data-row-remove', '');
    remove.classList.add('btn-danger');
    row.appendChild(remove);
  }

  function wireRows(scope) {
    scope.querySelectorAll('[data-rows]').forEach(function (group) {
      if (group.dataset.rowsReady) { return; }
      group.dataset.rowsReady = '1';
      var template = group.querySelector(':scope > template[data-row-template]');
      group.querySelectorAll(':scope > [data-row]').forEach(wireRow);
      if (!template) { return; }
      var add = document.createElement('button');
      add.type = 'button';
      add.className = 'btn btn-secondary';
      add.textContent = '+ ' + t('addRow');
      add.addEventListener('click', function () {
        var html = template.innerHTML.split('__ROW__').join(String(uid++));
        var holder = document.createElement('div');
        holder.innerHTML = html; // แม่แบบมาจากเซิร์ฟเวอร์ (escape แล้ว) — ไม่มีค่าที่ผู้ใช้พิมพ์
        var row = holder.firstElementChild;
        group.insertBefore(row, add);
        wireRow(row);
        wireRows(row);
      });
      group.appendChild(add);
    });
  }

  wireRows(document);

  // ------------------------------------------------------------ block builder

  var builder = document.querySelector('[data-block-builder]');

  function renumber(list) {
    list.querySelectorAll(':scope > [data-block]').forEach(function (block, i) {
      var sort = block.querySelector('input[name$="[sort]"]');
      if (sort) { sort.value = String((i + 1) * 10); }
    });
  }

  function wireBlock(block, list) {
    var tools = block.querySelector('[data-block-tools]');
    if (!tools || tools.dataset.ready) { return; }
    tools.dataset.ready = '1';
    tools.hidden = true; // ช่องลำดับ/ติ๊กลบ ใช้เมื่อไม่มี JS — มี JS ใช้ปุ่มแทน
    var bar = document.createElement('div');
    bar.className = 'row cms-block-bar';
    bar.appendChild(button(t('moveUp'), '↑', function () {
      var prev = block.previousElementSibling;
      if (prev) { list.insertBefore(block, prev); renumber(list); }
    }));
    bar.appendChild(button(t('moveDown'), '↓', function () {
      var next = block.nextElementSibling;
      if (next) { list.insertBefore(next, block); renumber(list); }
    }));
    var remove = button(t('removeBlock'), '×', function () {
      block.remove();
      renumber(list);
    });
    remove.classList.add('btn-danger', 'spacer');
    bar.appendChild(remove);
    block.insertBefore(bar, tools);
  }

  if (builder) {
    var list = builder.querySelector('[data-block-list]');
    list.querySelectorAll(':scope > [data-block]').forEach(function (b) { wireBlock(b, list); });

    var addButton = builder.querySelector('[data-block-add]');
    var select = builder.querySelector('select[name="add_block"]');
    if (addButton && select) {
      addButton.addEventListener('click', function (event) {
        var type = select.value;
        var template = builder.querySelector('template[data-block-template="' + CSS.escape(type) + '"]');
        if (!template) { return; } // ไม่ได้เลือก = ปล่อยให้ฟอร์มส่งตามปกติ
        event.preventDefault();
        var holder = document.createElement('div');
        holder.innerHTML = template.innerHTML.split('__BLOCK__').join('n' + String(uid++));
        var block = holder.firstElementChild;
        list.appendChild(block);
        wireBlock(block, list);
        wireRows(block);
        renumber(list);
        var empty = builder.querySelector('[data-block-empty]');
        if (empty) { empty.remove(); }
        var first = block.querySelector('input:not([type=hidden]), textarea, select');
        if (first) { first.focus(); }
      });
    }
  }

  // ------------------------------------------------------------ ตัวแก้ HTML

  function wrapSelection(area, before, after) {
    var start = area.selectionStart, end = area.selectionEnd;
    var text = area.value.slice(start, end);
    area.setRangeText(before + text + after, start, end, 'end');
    area.focus();
    area.dispatchEvent(new Event('input'));
  }

  function insertText(area, text) {
    area.setRangeText(text, area.selectionStart, area.selectionEnd, 'end');
    area.focus();
    area.dispatchEvent(new Event('input'));
  }

  function escapeAttr(value) {
    return String(value).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  document.querySelectorAll('textarea[data-html-editor]').forEach(function (area) {
    var bar = document.createElement('div');
    bar.className = 'row editor-toolbar';
    bar.setAttribute('role', 'toolbar');
    var tools = [
      ['h2', 'H2', function () { wrapSelection(area, '<h2>', '</h2>'); }],
      ['h3', 'H3', function () { wrapSelection(area, '<h3>', '</h3>'); }],
      ['p', '¶', function () { wrapSelection(area, '<p>', '</p>'); }],
      ['ul', '•', function () { wrapSelection(area, '<ul>\n<li>', '</li>\n</ul>'); }],
      ['ol', '1.', function () { wrapSelection(area, '<ol>\n<li>', '</li>\n</ol>'); }],
      ['strong', 'B', function () { wrapSelection(area, '<strong>', '</strong>'); }],
      ['em', 'I', function () { wrapSelection(area, '<em>', '</em>'); }],
      ['link', '🔗', function () {
        var href = window.prompt(t('linkPrompt'), 'https://');
        if (href) { wrapSelection(area, '<a href="' + escapeAttr(href) + '">', '</a>'); }
      }],
      ['table', '▦', function () {
        insertText(area, '<table>\n<thead><tr><th></th><th></th></tr></thead>\n<tbody><tr><td></td><td></td></tr></tbody>\n</table>\n');
      }]
    ];
    tools.forEach(function (tool) {
      var b = button(t(tool[0]), tool[1], tool[2]);
      bar.appendChild(b);
    });

    var images = [];
    try { images = JSON.parse(area.dataset.images || '[]'); } catch (e) { images = []; }
    var picker = document.createElement('select');
    picker.setAttribute('aria-label', t('image'));
    var first = document.createElement('option');
    first.value = '';
    first.textContent = images.length ? t('image') : t('imageNone');
    picker.appendChild(first);
    images.forEach(function (img, i) {
      var o = document.createElement('option');
      o.value = String(i);
      o.textContent = img.label;
      picker.appendChild(o);
    });
    picker.addEventListener('change', function () {
      var img = images[parseInt(picker.value, 10)];
      if (!img) { return; }
      var tag = '<img src="' + escapeAttr(img.src) + '" alt="' + escapeAttr(img.alt) + '"'
        + (img.width ? ' width="' + img.width + '"' : '') + (img.height ? ' height="' + img.height + '"' : '')
        + ' loading="lazy">';
      insertText(area, tag);
      picker.value = '';
    });
    bar.appendChild(picker);
    area.parentNode.insertBefore(bar, area);
  });
})();
