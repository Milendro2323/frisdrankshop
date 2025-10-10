// Verhoog waarde van een input (id) met 1
function inc(id) {
  var el = document.getElementById(id);
  if (!el) return;                 // stop als element niet bestaat
  el.value = (+el.value || 0) + 1; // + forceert nummer; fallback 0
}

// Verlaag waarde van een input (id) met 1, niet onder 0
function dec(id) {
  var el = document.getElementById(id);
  if (!el) return;                              // stop als element niet bestaat
  el.value = Math.max(0, (+el.value || 0) - 1); // ondergrens 0
}
