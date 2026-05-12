document.addEventListener("DOMContentLoaded", () => {
  // Elemente auswählen
  const charBtn = document.getElementById("char-btn");
  const charPopup = document.getElementById("char-popup");

  const lexBtn = document.getElementById("lexikon-btn");
  const lexPopup = document.getElementById("lexikon-popup");

  // Funktion zum Umschalten (Toggle)
  function togglePopup(popupToOpen, popupToClose) {
    // Schließe das andere Popup, falls es offen ist
    popupToClose.classList.remove("show");

    // Umschalten des gewünschten Popups
    if (popupToOpen.classList.contains("show")) {
      popupToOpen.classList.remove("show"); // Schließen, wenn schon offen
    } else {
      popupToOpen.classList.add("show"); // Öffnen
    }
  }

  // Klick-Events hinzufügen
  charBtn.addEventListener("click", (e) => {
    e.stopPropagation(); // Verhindert, dass der Klick das Schließen-Event auslöst
    togglePopup(charPopup, lexPopup);
  });

  lexBtn.addEventListener("click", (e) => {
    e.stopPropagation();
    togglePopup(lexPopup, charPopup);
  });

  // Optional: Schließen, wenn man irgendwo anders hinklickt
  document.addEventListener("click", (e) => {
    if (!charPopup.contains(e.target) && !charBtn.contains(e.target)) {
      charPopup.classList.remove("show");
    }
    if (!lexPopup.contains(e.target) && !lexBtn.contains(e.target)) {
      lexPopup.classList.remove("show");
    }
  });
});
