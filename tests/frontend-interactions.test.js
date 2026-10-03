import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import test from 'node:test';

test('dismiss, confirmation, code formatting and clipboard failure stay usable', async () => {
    const element = (dataset = {}) => ({ dataset, value: '', textContent: '', handlers: {}, classList: { remove() {} }, addEventListener(name, handler) { this.handlers[name] = handler; } });
    const dismiss = element();
    let removed = false;
    dismiss.closest = () => ({ remove() { removed = true; } });
    const form = element({ confirm: 'Batalkan?' });
    const input = element();
    const copy = element({ copyCode: 'pickup-code' });
    const status = element();
    const code = element();
    code.textContent = ' ABCD-1234 ';
    let copied;
    const clipboard = { async writeText(value) { copied = value; } };
    const selectors = { '[data-dismiss]': [dismiss], '[data-confirm]': [form], '[data-pickup-code]': [input], '[data-copy-code]': [copy] };
    runInNewContext(readFileSync(new URL('../resources/js/app.js', import.meta.url), 'utf8'), {
        document: { addEventListener(name, callback) { callback(); }, querySelectorAll(selector) { return selectors[selector] ?? []; }, getElementById(id) { return id === 'pickup-code' ? code : status; } },
        navigator: { clipboard }, window: { isSecureContext: true, confirm: () => false },
    });
    dismiss.handlers.click();
    assert.equal(removed, true);
    let prevented = false;
    form.handlers.submit({ preventDefault() { prevented = true; } });
    assert.equal(prevented, true);
    input.value = 'ab cd123456!';
    input.handlers.input();
    assert.equal(input.value, 'ABCD-1234');
    await copy.handlers.click();
    assert.equal(copied, 'ABCD-1234');
    clipboard.writeText = async () => { throw new Error('Denied'); };
    await copy.handlers.click();
    assert.match(status.textContent, /salin secara manual/);
});
