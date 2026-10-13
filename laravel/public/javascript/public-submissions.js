// Same-origin form submissions. Credentials stay on the server.
async function fitfusionSubmit(url, payload) {
    const response = await fetch(url, { method: 'POST', credentials: 'same-origin',
        headers: {'Content-Type':'application/json',Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content || ''},
        body: JSON.stringify(payload) });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Unable to submit. Please refresh and try again.');
    return data;
}
// Escape database content before the preserved card renderers insert HTML.
function fitfusionEscapeData(value) {
    if (typeof value === 'string') return value.replace(/[&<>"']/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[character]));
    if (Array.isArray(value)) return value.map(fitfusionEscapeData);
    if (value && typeof value === 'object') return Object.fromEntries(Object.entries(value).map(([key,item]) => [key,fitfusionEscapeData(item)]));
    return value;
}
