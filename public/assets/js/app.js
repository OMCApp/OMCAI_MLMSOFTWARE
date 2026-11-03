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
