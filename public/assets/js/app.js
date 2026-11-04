/**
 * JavaScript ทั้งระบบอยู่ไฟล์นี้ไฟล์เดียว
 *
 * ‼️ ไม่พึ่ง library ใดๆ และไม่โหลดจาก CDN
 * ‼️ ห้ามเขียน inline script ในหน้า เพราะ CSP ไม่อนุญาต unsafe-inline
 */

(() => {
    'use strict';

    /* ---------------------------------------------------------- เมนู */

    const sidebar = document.querySelector('[data-sidebar]');
    const toggle  = document.querySelector('[data-sidebar-toggle]');

    if (sidebar && toggle) {
        // จำสถานะพับ/กางไว้ ผู้ใช้จะได้ไม่ต้องพับใหม่ทุกครั้งที่เปลี่ยนหน้า
        const stored = localStorage.getItem('sidebar-collapsed');

        if (stored === 'true') {
            sidebar.dataset.collapsed = 'true';
        }

        toggle.addEventListener('click', () => {
            const isNarrow = window.matchMedia('(max-width: 768px)').matches;

            if (isNarrow) {
                sidebar.dataset.open = sidebar.dataset.open === 'true' ? 'false' : 'true';
                return;
            }

            const next = sidebar.dataset.collapsed !== 'true';
            sidebar.dataset.collapsed = String(next);
            localStorage.setItem('sidebar-collapsed', String(next));
        });
    }

    /* ------------------------------------------------- กันกดปุ่มซ้ำ */

    // กดรัวแล้วได้ข้อมูลซ้ำในฐานข้อมูล เป็นบั๊กที่เกิดกับทุกระบบที่ไม่ได้กันไว้
    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        form.querySelectorAll('[data-submit-once]').forEach((button) => {
            button.dataset.busy = 'true';
            button.setAttribute('aria-disabled', 'true');
        });
    });

    /* ------------------------------------------------- สลับภาษาอัตโนมัติ */

    document.querySelectorAll('[data-auto-submit]').forEach((element) => {
        element.addEventListener('change', () => element.form?.submit());
    });

    /* ---------------------------------------------------------- toast */

    document.querySelectorAll('[data-toast]').forEach((toast) => {
        const close = () => toast.remove();

        toast.querySelector('[data-toast-close]')?.addEventListener('click', close);
        window.setTimeout(close, 6000);
    });

    /* ------------------------------------------ ปิดกล่อง modal ทุกตัว */

    // ‼️ ผูกครั้งเดียวให้ทุกกล่องในระบบ กล่องใหม่ไม่ต้องเขียน handler ของตัวเอง
    //    ปุ่มปิดมาจาก partials/dialog.php ซึ่งเป็นเปลือกกลางของทุก modal
    document.querySelectorAll('[data-dialog-close]').forEach((button) => {
        button.addEventListener('click', () => button.closest('dialog')?.close());
    });

    /* ------------------------------------------------- ปุ่มแจ้งปัญหา */

    const dialog = document.getElementById('support-dialog');

    if (dialog) {
        const chooseStep = dialog.querySelector('[data-support-step="choose"]');
        const formStep   = dialog.querySelector('[data-support-step="form"]');
        const typeInput  = dialog.querySelector('[data-support-type-input]');

        const showChoose = () => {
            chooseStep.hidden = false;
            formStep.hidden   = true;
        };

        document.querySelectorAll('[data-support-open]').forEach((button) => {
            button.addEventListener('click', () => {
                showChoose();
                dialog.showModal();
            });
        });

        dialog.querySelectorAll('[data-support-type]').forEach((button) => {
            button.addEventListener('click', () => {
                typeInput.value   = button.dataset.supportType;
                chooseStep.hidden = true;
                formStep.hidden   = false;
                formStep.querySelector('textarea')?.focus();
            });
        });

        dialog.querySelector('[data-support-back]')?.addEventListener('click', showChoose);
    }

    /* --------------------------------- ยืนยันก่อนทำสิ่งที่ย้อนกลับยาก */

    // ‼️ กล่องถูกสร้างที่นี่ ไม่ได้เขียนไว้ในหน้า (design.md 5.11)
    //    ฟอร์มยัง submit ได้ตามปกติถ้าไฟล์นี้โหลดไม่ขึ้น ซึ่งดีกว่าปุ่มที่กดแล้วเงียบ
    const confirmForms = document.querySelectorAll('form[data-confirm]');

    if (confirmForms.length > 0) {
        const strings = document.getElementById('js-lang')?.dataset ?? {};

        let box     = null;
        let pending = null;

        const build = () => {
            const el   = document.createElement('dialog');
            const head = document.createElement('div');
            const body = document.createElement('div');
            const foot = document.createElement('div');

            el.className = 'dialog-sm';
            el.setAttribute('role', 'alertdialog');
            el.setAttribute('aria-labelledby', 'confirm-title');
            el.setAttribute('aria-describedby', 'confirm-body');

            head.className = 'dialog-head';
            body.className = 'dialog-body';
            foot.className = 'dialog-foot';

            const title = document.createElement('h2');
            title.id = 'confirm-title';

            const text = document.createElement('p');
            text.id = 'confirm-body';
            text.className = 'muted';

            // ปุ่มยกเลิกมาก่อนในลำดับ DOM เพราะมันต้องเป็นตัวที่ focus อยู่แต่แรก
            const cancel = document.createElement('button');
            cancel.type        = 'button';
            cancel.className   = 'btn';
            cancel.textContent = strings.cancel ?? '';

            const ok = document.createElement('button');
            ok.type      = 'button';
            ok.className = 'btn btn-danger';

            head.append(title);
            body.append(text);
            foot.append(cancel, ok);
            el.append(head, body, foot);
            document.body.append(el);

            cancel.addEventListener('click', () => el.close());

            ok.addEventListener('click', () => {
                el.close();

                if (pending === null) {
                    return;
                }

                // ‼️ ปล่อยให้ผ่าน listener เดิมอีกรอบ แทนการเรียก form.submit() ตรงๆ
                //    form.submit() ข้ามอีเวนต์ submit ทั้งหมด ซึ่งจะข้ามตัวกันกดซ้ำไปด้วย
                pending.dataset.confirmed = 'true';
                pending.requestSubmit();

                // ‼️ ต้องล้างธงทันที ปกติหน้าจะเปลี่ยนไปแล้วจึงไม่มีใครเห็น
                //    แต่ถ้า submit ถูกขวางไว้ หรือผู้ใช้กด back กลับมาเจอหน้าเดิมจาก bfcache
                //    ธงที่ค้างจะทำให้กดปุ่มลบครั้งถัดไป "ข้ามการยืนยัน" ไปเลย
                //    requestSubmit() ยิงอีเวนต์แบบ synchronous จึงล้างตรงนี้ได้ปลอดภัย
                delete pending.dataset.confirmed;
                pending = null;
            });

            return { el, title, text, ok };
        };

        confirmForms.forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (form.dataset.confirmed === 'true') {
                    return;
                }

                event.preventDefault();

                box ??= build();
                pending = form;

                // textContent เท่านั้น — ค่าพวกนี้มีชื่อรายการจากฐานข้อมูลอยู่ข้างใน
                box.title.textContent = form.dataset.confirmTitle ?? '';
                box.text.textContent  = form.dataset.confirmBody ?? '';
                box.ok.textContent    = form.dataset.confirmLabel ?? '';

                box.text.hidden = (form.dataset.confirmBody ?? '') === '';

                box.el.showModal();
                box.el.querySelector('.dialog-foot .btn')?.focus();
            });
        });
    }

    /* --------------------------------------------------------- tooltip */

    // ‼️ ย้าย title ไปเก็บที่ data-tip ตอนเริ่ม เพื่อไม่ให้ tooltip ของ OS โผล่ซ้อน
    //    ถ้าไฟล์นี้โหลดไม่ขึ้น title จะยังอยู่ครบและ tooltip ของ OS ยังทำงานตามเดิม
    //    ส่วน screen reader ใช้ aria-label อยู่แล้ว จึงไม่เสียอะไรทั้งสองทาง
    {
        const tip = document.createElement('div');
        tip.className = 'tooltip';
        tip.setAttribute('role', 'tooltip');
        tip.hidden = true;
        document.body.append(tip);

        document.querySelectorAll('[title]').forEach((el) => {
            el.dataset.tip = el.getAttribute('title') ?? '';
            el.removeAttribute('title');
        });

        const hide = () => {
            tip.hidden = true;
            delete tip.dataset.shown;
        };

        const show = (target) => {
            const text = target.dataset.tip ?? '';

            if (text === '') {
                return;
            }

            tip.textContent = text;
            tip.hidden = false;

            const box = target.getBoundingClientRect();
            const own = tip.getBoundingClientRect();

            // วางไว้เหนือปุ่ม ถ้าชนขอบบนให้ย้ายไปอยู่ใต้แทน
            const above = box.top - own.height - 8;
            const left  = Math.min(
                Math.max(8, box.left + (box.width - own.width) / 2),
                window.innerWidth - own.width - 8
            );

            tip.style.left = left + 'px';
            tip.style.top  = (above < 8 ? box.bottom + 8 : above) + 'px';
            tip.dataset.shown = 'true';
        };

        const near = (event) => event.target instanceof Element
            ? event.target.closest('[data-tip]')
            : null;

        document.addEventListener('pointerover', (event) => {
            const target = near(event);
            target === null ? hide() : show(target);
        });

        document.addEventListener('focusin', (event) => {
            const target = near(event);

            if (target !== null) {
                show(target);
            }
        });

        ['pointerdown', 'focusout', 'scroll'].forEach((name) => {
            document.addEventListener(name, hide, true);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                hide();
            }
        });
    }

    /* ------------------------------------------------------ แนบไฟล์ */

    const uploadFields = document.querySelectorAll('[data-upload]');

    if (uploadFields.length > 0) {
        const strings = document.getElementById('js-lang')?.dataset ?? {};

        const humanSize = (bytes) => {
            // ‼️ ใช้ Intl ของเบราว์เซอร์ ไม่ส่งหน่วยมาจาก PHP (i18n.md 6ก ข้อ 4)
            const units = ['B', 'KB', 'MB'];
            let value = bytes;
            let unit  = 0;

            while (value >= 1024 && unit < units.length - 1) {
                value /= 1024;
                unit++;
            }

            return new Intl.NumberFormat(strings.locale, { maximumFractionDigits: 1 })
                .format(value) + ' ' + units[unit];
        };

        const fill = (text, values) =>
            Object.entries(values).reduce((out, [k, v]) => out.split(':' + k).join(v), text ?? '');

        uploadFields.forEach((field) => {
            const input   = field.querySelector('input[type="file"]');
            const maxSize = Number(field.dataset.uploadMax || 0);
            const accept  = (field.dataset.uploadAccept || '').split(',').filter(Boolean);
            const preview = field.dataset.uploadPreview === '1';

            if (input === null) {
                return;
            }

            // ‼️ เก็บไฟล์ไว้เองเพื่อให้ "ลบทีละไฟล์" ทำได้
            //    FileList แก้ไม่ได้โดยตรง ต้องประกอบใหม่ผ่าน DataTransfer แล้วยัดกลับเข้า input
            //    ทำแบบนี้ฟอร์มจึง submit ด้วย POST ปกติได้ ไม่ต้องมี endpoint พิเศษ
            let picked = [];

            const zone = document.createElement('button');
            zone.type      = 'button';
            zone.className = 'upload-zone';
            zone.textContent = strings.uploadDrop ?? '';

            const list = document.createElement('div');
            list.className = 'upload-list';

            input.hidden = true;
            input.after(zone, list);

            const why = (file) => {
                const ext = (file.name.split('.').pop() ?? '').toLowerCase();

                if (accept.length > 0 && !accept.includes(ext)) {
                    return fill(strings.uploadBadType, { name: file.name });
                }
                if (maxSize > 0 && file.size > maxSize) {
                    return fill(strings.uploadTooLarge, { name: file.name, size: humanSize(maxSize) });
                }

                return '';
            };

            const sync = () => {
                // ส่งเฉพาะไฟล์ที่ผ่านเงื่อนไข — ไฟล์ที่ไม่ผ่านยังโชว์อยู่พร้อมเหตุผล
                const box = new DataTransfer();
                picked.filter((file) => why(file) === '').forEach((file) => box.items.add(file));
                input.files = box.files;
            };

            const render = () => {
                list.textContent = '';

                picked.forEach((file, index) => {
                    const reason = why(file);
                    const row    = document.createElement('div');
                    row.className = 'upload-item';

                    if (reason !== '') {
                        row.dataset.invalid = 'true';
                    }

                    if (preview && file.type.startsWith('image/')) {
                        const img = document.createElement('img');
                        img.className = 'upload-thumb';
                        img.alt = '';
                        img.src = URL.createObjectURL(file);
                        img.addEventListener('load', () => URL.revokeObjectURL(img.src), { once: true });
                        row.append(img);
                    }

                    const name = document.createElement('span');
                    name.className   = 'upload-name';
                    name.textContent = file.name;

                    const size = document.createElement('span');
                    size.className   = 'upload-size';
                    size.textContent = humanSize(file.size);

                    const drop = document.createElement('button');
                    drop.type      = 'button';
                    drop.className = 'btn btn-icon';
                    drop.setAttribute('aria-label', strings.uploadRemove ?? '');
                    drop.title     = strings.uploadRemove ?? '';
                    drop.textContent = '×';
                    drop.addEventListener('click', () => {
                        picked.splice(index, 1);
                        sync();
                        render();
                    });

                    row.append(name, size);

                    // ‼️ ไฟล์ที่ไม่ผ่านต้องบอกเหตุผล "รายไฟล์" ไม่ใช่ปฏิเสธทั้งชุด
                    if (reason !== '') {
                        const note = document.createElement('span');
                        note.className   = 'upload-why';
                        note.textContent = reason;
                        row.append(note);
                    }

                    row.append(drop);
                    list.append(row);
                });
            };

            const take = (files) => {
                const incoming = [...files];
                picked = input.multiple ? picked.concat(incoming) : incoming.slice(0, 1);
                sync();
                render();
            };

            zone.addEventListener('click', () => input.click());
            input.addEventListener('change', () => take(input.files));

            ['dragenter', 'dragover'].forEach((name) => {
                zone.addEventListener(name, (event) => {
                    event.preventDefault();
                    zone.dataset.dragging = 'true';
                    zone.textContent = strings.uploadDropActive ?? '';
                });
            });

            ['dragleave', 'drop'].forEach((name) => {
                zone.addEventListener(name, (event) => {
                    event.preventDefault();
                    delete zone.dataset.dragging;
                    zone.textContent = strings.uploadDrop ?? '';
                });
            });

            zone.addEventListener('drop', (event) => take(event.dataTransfer?.files ?? []));
        });
    }

    /* ------------------------------------------- ปุ่มลัดช่วงวัน */

    const ranges = {
        today:   () => [new Date(), new Date()],
        week:    () => [shift(-7), new Date()],
        month:   () => [shift(-30), new Date()],
        '6months': () => [shift(-182), new Date()],
        year:    () => [shift(-365), new Date()],
    };

    function shift(days) {
        const date = new Date();
        date.setDate(date.getDate() + days);
        return date;
    }

    // ‼️ ส่งค่าเป็นคริสต์ศักราชเสมอ แม้หน้าจะแสดงเป็น พ.ศ. ให้ผู้ใช้เห็น
    //
    // ‼️ ห้ามใช้ toISOString() เพื่อเอาวันที่ — มันแปลงเป็น UTC ก่อน
    //    ไทยเป็น UTC+7 จึงเลื่อนย้อนหลัง 7 ชั่วโมง แปลว่าทุกครั้งที่กดปุ่มลัด "วันนี้"
    //    ก่อน 07:00 ตามเวลาไทย จะได้ช่วงวันของเมื่อวาน โดยไม่มี error ใดๆ แจ้ง
    //    ร้านที่เปิดดึกและตัดรอบตอนตี 4 คือเวลาที่ใช้ปุ่มนี้พอดี
    //    ต้องประกอบจาก getFullYear/getMonth/getDate ซึ่งเป็นเวลาเครื่อง ไม่แปลงเขตเวลา
    function isoDate(date) {
        const pad = (n) => String(n).padStart(2, '0');

        return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
    }

    document.querySelectorAll('[data-quick-range] [data-range]').forEach((button) => {
        button.addEventListener('click', () => {
            const build = ranges[button.dataset.range];

            if (!build) {
                return;
            }

            const [from, to] = build();
            const form = button.closest('form');

            if (!form) {
                return;
            }

            form.querySelector('[name="from"]').value = isoDate(from);
            form.querySelector('[name="to"]').value   = isoDate(to);
            form.submit();
        });
    });

    /* --------------------------------------------- dropdown ที่ค้นได้ */

    /* เกณฑ์อยู่ที่ standard/components.md ข้อ 3ก — 1-8 ใช้ select ปกติ · เกิน 8 ต้องค้นได้
       เกิน 50 ให้ตั้ง data-source เพื่อค้นจากฝั่ง server แทนการโหลดทั้งตารางลงหน้า

       ‼️ <select> ตัวจริงยังอยู่ในหน้าเสมอ เป็นตัวถือค่าและตัวที่ฟอร์มส่ง
          JS พังเมื่อไหร่ก็ยังเหลือ select ที่ใช้งานได้ ไม่ใช่ช่องที่กดแล้วไม่เกิดอะไร
       ‼️ ห้ามซ่อน select ด้วย display:none — required ที่ซ่อนแบบนั้นทำให้ฟอร์มส่งไม่ได้
          โดยเบราว์เซอร์ไม่ขึ้นข้อความอะไรให้ผู้ใช้เห็นเลย */

    /* ‼️ ครอบ <select> ทุกตัว ไม่ใช่เฉพาะตัวที่ค้นได้ (3.20.0)
          รายการที่กางออกมาของ select เป็นของ OS — จัดสไตล์ไม่ได้เลยไม่ว่าเขียน CSS แค่ไหน
          ปล่อยไว้คือทุกโปรเจคมีแถบน้ำเงินของ Windows โผล่ทับหน้าที่ออกแบบมา (components.md ข้อ 5.12)
          ช่องค้นจะโผล่เฉพาะตัวที่มี data-search หรือ data-source เท่านั้น (ข้อ 3ก)
          หน้าไหนอยากได้ select ของ OS จริงๆ ใส่ data-native */

    document.querySelectorAll('select:not([data-native])').forEach((select) => {
        const strings    = document.getElementById('js-lang')?.dataset ?? {};
        const source     = select.dataset.source ?? '';
        const searchable = source !== '' || select.hasAttribute('data-search');
        const wrap    = document.createElement('div');
        const input   = document.createElement('input');
        const list    = document.createElement('ul');
        const listId  = select.id + '-list';
        const label   = document.querySelector('label[for="' + CSS.escape(select.id) + '"]');

        let items    = [];   // รายการที่กำลังแสดงอยู่
        let active   = -1;   // แถวที่ไฮไลต์ ใช้กับคีย์บอร์ด
        let page     = 1;
        let hasMore  = false;
        let timer    = 0;
        let sequence = 0;    // กันผลของคำค้นเก่ามาทับคำค้นใหม่

        if (label && label.id === '') {
            label.id = select.id + '-label';
        }

        wrap.className     = 'combobox';
        input.type         = 'text';
        input.className    = 'combobox-input';
        input.id           = select.id + '-search';
        input.autocomplete = 'off';

        // ตัวที่ค้นไม่ได้เป็นแค่ปุ่มเปิดรายการ — พิมพ์ลงไปไม่ได้ แต่ยังโฟกัสและกดคีย์บอร์ดได้
        input.readOnly    = !searchable;
        input.placeholder = searchable ? (strings.search ?? '') : '';
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-expanded', 'false');
        input.setAttribute('aria-controls', listId);
        input.setAttribute('aria-autocomplete', 'list');

        if (label) {
            input.setAttribute('aria-labelledby', label.id);
        }

        if (select.hasAttribute('aria-describedby')) {
            input.setAttribute('aria-describedby', select.getAttribute('aria-describedby'));
        }

        list.className = 'combobox-list';
        list.id        = listId;
        list.hidden    = true;
        list.setAttribute('role', 'listbox');

        /* นอกฟอร์มไม่มีใครกำหนดความกว้างให้ จึงต้องบอกขนาดจากตัวเลือกที่ยาวที่สุดเอง */
        if (select.closest('.field') === null) {
            const longest = [...select.options].reduce((max, option) => Math.max(max, option.textContent.trim().length), 0);

            input.size = Math.max(4, longest + 1);
        }

        select.tabIndex = -1;
        select.setAttribute('aria-hidden', 'true');
        select.parentNode.insertBefore(wrap, select);
        wrap.append(select, input, list);

        // คลิก label หรือ validate ไม่ผ่านแล้วเบราว์เซอร์โฟกัสมาที่ select → ส่งต่อให้ช่องค้น
        select.addEventListener('focus', () => input.focus());

        const labelOf = (value) =>
            select.querySelector('option[value="' + CSS.escape(value) + '"]')?.textContent.trim() ?? '';

        /* ‼️ ต้องอ่านป้ายของ option ที่ถูกเลือกเสมอ แม้ค่าจะเป็นค่าว่าง
           ตัวเลือกอย่าง "ไม่ระบุ" มีค่าเป็นค่าว่าง — เช็ค value === '' แล้วไม่แสดงอะไร
           ทำให้ช่องว่างเปล่าทั้งที่ผู้ใช้เลือกไว้แล้ว */
        const showSelected = () => {
            input.value = labelOf(select.value);
        };

        const close = () => {
            list.hidden = true;
            wrap.dataset.open = 'false';
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
            active = -1;
            showSelected();
        };

        const choose = (item) => {
            if (!item) {
                return;
            }

            if (select.querySelector('option[value="' + CSS.escape(item.value) + '"]') === null) {
                // ค่าที่ได้จาก server ต้องมีเป็น option ด้วย ไม่งั้นฟอร์มส่งค่าว่าง
                select.add(new Option(item.label, item.value));
            }

            select.value = item.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            close();
            input.focus();
        };

        const highlight = (index) => {
            const rows = list.querySelectorAll('.combobox-option');

            active = index;
            rows.forEach((row, i) => row.setAttribute('aria-selected', String(i === index)));

            if (index < 0 || rows[index] === undefined) {
                input.removeAttribute('aria-activedescendant');
                return;
            }

            input.setAttribute('aria-activedescendant', rows[index].id);
            rows[index].scrollIntoView({ block: 'nearest' });
        };

        const message = (text) => {
            const row = document.createElement('li');

            row.className   = 'combobox-empty';
            row.textContent = text;
            list.replaceChildren(row);
        };

        const render = (append) => {
            const rows = items.map((item, index) => {
                const row = document.createElement('li');

                row.className     = 'combobox-option';
                row.id            = listId + '-' + index;
                row.dataset.value = item.value;
                row.textContent   = item.label;
                row.setAttribute('role', 'option');
                row.setAttribute('aria-selected', 'false');

                if (item.hint) {
                    const hint = document.createElement('span');

                    hint.className   = 'combobox-hint';
                    hint.textContent = item.hint;
                    row.append(hint);
                }

                return row;
            });

            if (rows.length === 0) {
                message(strings.noResults ?? '');
                return;
            }

            if (hasMore) {
                const more = document.createElement('li');

                more.className    = 'combobox-more';
                more.textContent  = strings.loadMore ?? '';
                more.dataset.more = 'true';
                rows.push(more);
            }

            list.replaceChildren(...rows);
            highlight(append ? active : 0);
        };

        const open = () => {
            list.hidden = false;
            wrap.dataset.open = 'true';
            input.setAttribute('aria-expanded', 'true');
        };

        /* ‼️ ไม่กรอง option ที่ค่าว่างทิ้ง — ตัวเลือกอย่าง "ไม่ระบุ" คือวิธีเดียวที่ผู้ใช้ยกเลิกค่าที่เลือกไว้ */
        const localItems = (term) => [...select.options]
            .filter((option) => option.textContent.toLowerCase().includes(term.toLowerCase()))
            .map((option) => ({ value: option.value, label: option.textContent.trim(), hint: '' }));

        const fetchItems = (term, append) => {
            const ticket = ++sequence;

            open();
            message(strings.loading ?? '');

            fetch(source + '?q=' + encodeURIComponent(term) + '&page=' + page, {
                headers: { 'X-Requested-With': 'fetch' },
            })
                .then((response) => response.json())
                .then((body) => {
                    if (ticket !== sequence) {
                        return; // มีคำค้นใหม่แซงไปแล้ว ผลชุดนี้ทิ้ง
                    }

                    if (body.success !== true) {
                        message(strings.noResults ?? '');
                        return;
                    }

                    const min = Number(body.data.min ?? 2);

                    if (term !== '' && term.length < min) {
                        message((strings.typeMore ?? '').replace('{n}', String(min)));
                        return;
                    }

                    items   = append ? items.concat(body.data.items) : body.data.items;
                    hasMore = body.data.more === true;
                    render(append);
                })
                .catch(() => message(strings.noResults ?? ''));
        };

        const search = (term, append = false) => {
            if (source === '') {
                items   = localItems(term);
                hasMore = false;
                open();
                render(append);
                return;
            }

            window.clearTimeout(timer);
            // หน่วงก่อนยิง ไม่งั้นได้ query ทุกครั้งที่กดคีย์
            timer = window.setTimeout(() => fetchItems(term, append), 250);
        };

        const openList = () => {
            page = 1;

            if (searchable) {
                input.value = '';
                search('');
                return;
            }

            search('');
            // ตัวที่ค้นไม่ได้ ให้เริ่มไฮไลต์ที่ค่าที่เลือกอยู่ ไม่ใช่แถวแรกเสมอ
            highlight(Math.max(0, items.findIndex((item) => item.value === select.value)));
        };

        input.addEventListener('focus', openList);

        input.addEventListener('mousedown', (event) => {
            // กดซ้ำที่ช่องเดิมต้องปิดรายการ ไม่ใช่เปิดค้างไว้ตลอด
            if (document.activeElement === input && !list.hidden) {
                event.preventDefault();
                close();
            }
        });

        input.addEventListener('input', () => {
            page = 1;
            search(input.value);
        });

        input.addEventListener('keydown', (event) => {
            const rows = list.querySelectorAll('.combobox-option');

            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();

                if (list.hidden) {
                    search(input.value);
                    return;
                }

                if (rows.length > 0) {
                    const step = event.key === 'ArrowDown' ? 1 : -1;

                    highlight((active + step + rows.length) % rows.length);
                }

                return;
            }

            if ((event.key === 'Home' || event.key === 'End') && !list.hidden && rows.length > 0) {
                event.preventDefault();
                highlight(event.key === 'Home' ? 0 : rows.length - 1);
                return;
            }

            if (event.key === 'Enter' && !list.hidden && active >= 0) {
                event.preventDefault();
                choose(items[active]);
                return;
            }

            if (event.key === 'Escape' && !list.hidden) {
                event.preventDefault();
                close();
                return;
            }

            // พิมพ์ตัวอักษรบนตัวที่ค้นไม่ได้ → กระโดดไปรายการที่ขึ้นต้นด้วยตัวนั้น เหมือน select ของ OS
            if (!searchable && event.key.length === 1 && !event.ctrlKey && !event.altKey && !event.metaKey) {
                if (list.hidden) {
                    openList();
                }

                const found = items.findIndex((item) => item.label.toLowerCase().startsWith(event.key.toLowerCase()));

                if (found >= 0) {
                    event.preventDefault();
                    highlight(found);
                }
            }
        });

        list.addEventListener('mousedown', (event) => {
            // mousedown ไม่ใช่ click เพราะ blur ของช่องค้นปิดรายการไปก่อน
            const row = event.target.closest('.combobox-option, .combobox-more');

            if (row === null) {
                return;
            }

            event.preventDefault();

            if (row.dataset.more === 'true') {
                page += 1;
                search(input.value, true);
                return;
            }

            choose(items.find((item) => item.value === row.dataset.value));
        });

        input.addEventListener('blur', () => window.setTimeout(close, 0));

        showSelected();
    });
})();

/* ================================================================== เว็บสาธารณะ
 *
 * header (เมนูกาง · drawer มือถือ · เงาตอนเลื่อน) · reveal · ตัวนับ · แท็บ · accordion · slider · parallax
 *
 * ‼️ ทุกอย่างเป็นการเสริม — หน้าต้องใช้ได้ครบเมื่อไม่มี JS (design.md 5.11)
 *    เนื้อหาที่ "รอเลื่อนมาถึง" ถูกซ่อนเฉพาะเมื่อไฟล์นี้ใส่ data-motion="on" ที่ <html>
 * ‼️ prefers-reduced-motion: ไม่ใส่ data-motion · ไม่วิ่งตัวนับ · ไม่ parallax
 * ‼️ ไม่มีข้อความที่ผู้ใช้เห็นในไฟล์นี้ — ป้ายทุกตัวมาจาก HTML ที่ผ่าน __() แล้ว
 */
(() => {
    'use strict';

    const root = document.documentElement;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const canObserve = 'IntersectionObserver' in window;
    const motionOn = !reduceMotion && canObserve;

    /* ------------------------------------------------------------ reveal */

    const reveals = Array.from(document.querySelectorAll('.reveal'));

    if (motionOn && reveals.length > 0) {
        // ‼️ ของที่อยู่ในจอแรกแล้วต้องไม่ถูกซ่อนแล้วโผล่ใหม่ (กะพริบ) — ทำเครื่องหมายก่อนเปิด data-motion
        const fold = window.innerHeight;

        reveals.forEach((element) => {
            if (element.getBoundingClientRect().top < fold * 0.92) {
                element.classList.add('is-visible');
            }
        });

        root.dataset.motion = 'on';

        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });

        reveals.filter((element) => !element.classList.contains('is-visible'))
            .forEach((element) => revealObserver.observe(element));
    } else {
        reveals.forEach((element) => element.classList.add('is-visible'));
    }

    /* ------------------------------------------------------------ ตัวนับ */

    // ‼️ ค่าจริงอยู่ใน HTML อยู่แล้ว — ไม่มี JS หรือลดการเคลื่อนไหว = เห็นค่าสุดท้ายทันที
    const counters = Array.from(document.querySelectorAll('[data-count]'));

    if (motionOn && counters.length > 0) {
        const locale = document.getElementById('js-lang')?.dataset.locale || root.lang || 'th';

        const run = (element) => {
            const target = Number(element.dataset.count);

            if (!Number.isFinite(target)) {
                return;
            }

            const decimals = (String(element.dataset.count).split('.')[1] || '').length;
            const format = new Intl.NumberFormat(locale, { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
            const duration = 1400;
            const start = performance.now();

            const step = (now) => {
                const progress = Math.min(1, (now - start) / duration);
                const eased = 1 - Math.pow(1 - progress, 3);

                element.textContent = format.format(target * eased);

                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };

            window.requestAnimationFrame(step);
        };

        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    counterObserver.unobserve(entry.target);
                    run(entry.target);
                }
            });
        }, { threshold: 0.4 });

        counters.forEach((element) => counterObserver.observe(element));
    }

    /* ------------------------------------------------------------ header */

    const header = document.querySelector('[data-site-header]');

    if (header) {
        header.dataset.enhanced = 'true';

        // เงาเบาๆ เมื่อเลื่อนลง — บอกว่า header ลอยอยู่เหนือเนื้อหา
        const onScroll = () => {
            header.dataset.scrolled = window.scrollY > 8 ? 'true' : 'false';
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });

        const menus = Array.from(header.querySelectorAll('[data-menu]'));
        const toggleOf = (menu) => menu.querySelector('[data-menu-toggle]');
        const linksOf = (menu) => Array.from(menu.querySelectorAll('[data-menu-panel] a'));

        const setMenu = (menu, open) => {
            toggleOf(menu).setAttribute('aria-expanded', open ? 'true' : 'false');
        };
        const closeMenus = (except = null) => {
            menus.filter((menu) => menu !== except).forEach((menu) => setMenu(menu, false));
        };

        menus.forEach((menu) => {
            const toggle = toggleOf(menu);

            toggle.addEventListener('click', () => {
                const open = toggle.getAttribute('aria-expanded') !== 'true';
                closeMenus(menu);
                setMenu(menu, open);
            });

            toggle.addEventListener('keydown', (event) => {
                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    closeMenus(menu);
                    setMenu(menu, true);
                    linksOf(menu)[0]?.focus();
                }
            });

            menu.addEventListener('keydown', (event) => {
                const links = linksOf(menu);
                const index = links.indexOf(document.activeElement);

                if (event.key === 'Escape') {
                    setMenu(menu, false);
                    toggle.focus();
                } else if (index >= 0 && (event.key === 'ArrowDown' || event.key === 'ArrowUp')) {
                    event.preventDefault();
                    const next = event.key === 'ArrowDown' ? index + 1 : index - 1;
                    links[(next + links.length) % links.length].focus();
                }
            });

            // ‼️ จอใหญ่: focus ออกจากเมนูแล้วต้องปิด ไม่งั้นแผงค้างทับหน้า
            menu.addEventListener('focusout', (event) => {
                if (!menu.contains(event.relatedTarget) && !isDrawer()) {
                    setMenu(menu, false);
                }
            });
        });

        document.addEventListener('click', (event) => {
            if (!isDrawer() && !event.target.closest('[data-menu]')) {
                closeMenus();
            }
        });

        /* ---- drawer มือถือ — ปุ่มมี hidden ใน HTML เพราะใช้ได้ด้วย JS เท่านั้น */
        const nav = header.querySelector('[data-site-nav]');
        const openButton = header.querySelector('[data-site-nav-open]');
        const closeButton = header.querySelector('[data-site-nav-close]');
        const backdrop = header.querySelector('[data-site-nav-backdrop]');
        const drawerQuery = window.matchMedia('(max-width: 1199.98px)');
        const isDrawer = () => drawerQuery.matches;

        const setDrawer = (open) => {
            header.dataset.navOpen = open ? 'true' : 'false';
            root.dataset.navOpen = open ? 'true' : 'false';
            openButton?.setAttribute('aria-expanded', open ? 'true' : 'false');

            if (open) {
                closeButton?.focus();
            } else {
                closeMenus();
            }
        };

        if (nav && openButton) {
            openButton.hidden = false;

            if (closeButton) {
                closeButton.hidden = false;
            }

            openButton.addEventListener('click', () => setDrawer(true));
            closeButton?.addEventListener('click', () => {
                setDrawer(false);
                openButton.focus();
            });
            backdrop?.addEventListener('click', () => setDrawer(false));

            document.addEventListener('keydown', (event) => {
                if (header.dataset.navOpen !== 'true') {
                    return;
                }

                if (event.key === 'Escape') {
                    setDrawer(false);
                    openButton.focus();
                    return;
                }

                // ‼️ กัน Tab หลุดออกไปหลัง drawer — ผู้ใช้คีย์บอร์ดจะหลงอยู่ใต้ชั้นมืด
                if (event.key === 'Tab') {
                    const focusables = Array.from(nav.querySelectorAll('a[href], button:not([hidden])'))
                        .filter((element) => element.offsetParent !== null);
                    const first = focusables[0];
                    const last = focusables[focusables.length - 1];

                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (!event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                }
            });

            drawerQuery.addEventListener('change', () => setDrawer(false));
        }
    }

    /* ------------------------------------------------------------ แท็บ */

    document.querySelectorAll('[data-tabs]').forEach((container) => {
        const list = container.querySelector('[role="tablist"]');
        const tabs = Array.from(container.querySelectorAll('[role="tab"]'));

        if (!list || tabs.length === 0) {
            return;
        }

        const select = (tab, focus = false) => {
            tabs.forEach((other) => {
                const on = other === tab;
                other.setAttribute('aria-selected', on ? 'true' : 'false');
                other.tabIndex = on ? 0 : -1;

                const panel = document.getElementById(other.getAttribute('aria-controls'));
                if (panel) {
                    panel.hidden = !on;
                }
            });

            if (focus) {
                tab.focus();
            }
        };

        // ‼️ ไม่มี JS = เห็นทุกแผงเรียงกัน · มี JS = เปิดแถบแท็บแล้วซ่อนแผงที่ไม่ได้เลือก
        list.hidden = false;
        select(tabs.find((tab) => tab.getAttribute('aria-selected') === 'true') || tabs[0]);

        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => select(tab));
            tab.addEventListener('keydown', (event) => {
                const keys = { ArrowRight: index + 1, ArrowLeft: index - 1, Home: 0, End: tabs.length - 1 };

                if (event.key in keys) {
                    event.preventDefault();
                    select(tabs[(keys[event.key] + tabs.length) % tabs.length], true);
                }
            });
        });
    });

    /* ------------------------------------------------------------ accordion */

    // <details name> เปิดได้ทีละข้อในเบราว์เซอร์ใหม่อยู่แล้ว — ตัวนี้ทำให้เบราว์เซอร์เก่าได้พฤติกรรมเดียวกัน
    document.querySelectorAll('details[name]').forEach((details) => {
        details.addEventListener('toggle', () => {
            if (!details.open) {
                return;
            }

            document.querySelectorAll(`details[name="${CSS.escape(details.getAttribute('name'))}"]`).forEach((other) => {
                if (other !== details) {
                    other.open = false;
                }
            });
        });
    });

    /* ------------------------------------------------------------ slider */

    document.querySelectorAll('[data-slider]').forEach((slider) => {
        const track = slider.querySelector('[data-slider-track]');
        const controls = slider.querySelector('[data-slider-controls]');

        if (!track || !controls) {
            return;
        }

        controls.hidden = false;

        const move = (direction) => {
            const card = track.firstElementChild;
            const width = card ? card.getBoundingClientRect().width : track.clientWidth;
            track.scrollBy({ left: direction * width, behavior: reduceMotion ? 'auto' : 'smooth' });
        };

        slider.querySelector('[data-slider-prev]')?.addEventListener('click', () => move(-1));
        slider.querySelector('[data-slider-next]')?.addEventListener('click', () => move(1));
    });

    /* ------------------------------------------------------------ parallax เบาๆ */

    // ‼️ ขยับแค่ของตกแต่ง (แสงพื้นหลัง · แผงโลโก้) ผ่าน --parallax-y ที่ CSS อ่านใน transform เท่านั้น
    const layers = Array.from(document.querySelectorAll('[data-parallax]'));

    if (motionOn && layers.length > 0) {
        let ticking = false;

        const update = () => {
            const middle = window.innerHeight / 2;

            layers.forEach((layer) => {
                const rect = layer.getBoundingClientRect();

                if (rect.bottom < 0 || rect.top > window.innerHeight) {
                    return;
                }

                const factor = Number(layer.dataset.parallax) || 0;
                const offset = (rect.top + rect.height / 2 - middle) * factor * -1;
                layer.style.setProperty('--parallax-y', `${offset.toFixed(1)}px`);
            });

            ticking = false;
        };

        window.addEventListener('scroll', () => {
            if (!ticking) {
                ticking = true;
                window.requestAnimationFrame(update);
            }
        }, { passive: true });

        update();
    }

    /* ------------------------------------------------------------ analytics events */

    // demo_click · contact_click · lead_submit → dataLayer (GTM) และ gtag (GA4) ถ้าเปิดใช้ในตั้งค่าเว็บ
    // ‼️ ไม่ส่งข้อมูลที่ผู้ใช้กรอก — แค่ชื่อเหตุการณ์ + ปลายทางของลิงก์ / ชนิดฟอร์ม
    const track = (event, params) => {
        if (Array.isArray(window.dataLayer)) {
            window.dataLayer.push(Object.assign({ event }, params));
        }
        if (typeof window.gtag === 'function') {
            window.gtag('event', event, params);
        }
    };

    const linkEvent = (href) => {
        if (/demomlm\.|\/demo\/$/.test(href)) {
            return 'demo_click';
        }
        if (/^(tel:|mailto:)|line\.me|#lead-form$|\/contact\/$/.test(href)) {
            return 'contact_click';
        }
        return '';
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (!link || !link.closest('.site-body')) {
            return;
        }

        const href = link.getAttribute('href') || '';
        const name = link.dataset.track || linkEvent(href);

        if (name !== '') {
            track(name, { link_url: link.href, page_path: window.location.pathname });
        }
    });

    document.querySelectorAll('.lead-form').forEach((form) => {
        form.addEventListener('submit', () => {
            const key = form.querySelector('input[name="form_key"]');
            track('lead_submit', { form_key: key ? key.value : '', page_path: window.location.pathname });
        });
    });
})();
