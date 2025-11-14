// Robusteste Lösung für Rechtsklick-Flaggen
document.addEventListener('DOMContentLoaded', function() {
    // AJAX-Funktionalität für Zellen-Klicks
    function handleCellClick(e) {
        e.preventDefault();
        
        const cell = e.currentTarget;
        const href = cell.getAttribute('href');
        const scrollPosition = window.scrollY;
        
        // AJAX-Request statt Seitenneuladung
        fetch(href, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            // Parsen der HTML-Antwort
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Finde das game-container Element in der Antwort
            const newGameContainer = doc.querySelector('.game-container');
            const newSettings = doc.querySelector('fieldset');
            
            if (newGameContainer) {
                // Ersetze nur den Spielbereich
                const currentGameContainer = document.querySelector('.game-container');
                currentGameContainer.innerHTML = newGameContainer.innerHTML;
                
                // Event-Listener erneut hinzufügen
                addCellEventListeners();
            }
            
            if (newSettings) {
                // Ersetze auch die Einstellungen falls vorhanden
                const currentSettings = document.querySelector('fieldset');
                if (currentSettings && newSettings) {
                    currentSettings.innerHTML = newSettings.innerHTML;
                }
            }
            
            // Scroll-Position beibehalten
            window.scrollTo(0, scrollPosition);
        })
        .catch(error => {
            console.error('Fehler beim Aktualisieren:', error);
            // Fallback: Normales Neuladen
            window.location.href = href;
        });
    }

    // Event-Listener zu allen Zellen hinzufügen
    function addCellEventListeners() {
        const cells = document.querySelectorAll('a.cell');
        cells.forEach(cell => {
            cell.addEventListener('click', handleCellClick);
        });
    }

    // Initial Event-Listener hinzufügen
    addCellEventListeners();

    // Deaktiviert das Kontextmenü komplett auf dem Spielfeld
    document.querySelector('.game-container').addEventListener('contextmenu', function(e) {
        e.preventDefault();
        
        // Findet die angeklickte Zelle
        const cell = e.target.closest('a.cell');
        if (cell && !cell.classList.contains('revealed') && !cell.classList.contains('bomb')) {
            const href = cell.getAttribute('href');
            const flagHref = href + '&flag=1';
            const scrollPosition = window.scrollY;
            
            // AJAX für Rechtsklick (Flagge setzen)
            fetch(flagHref, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newGameContainer = doc.querySelector('.game-container');
                
                if (newGameContainer) {
                    const currentGameContainer = document.querySelector('.game-container');
                    currentGameContainer.innerHTML = newGameContainer.innerHTML;
                    addCellEventListeners();
                }
                
                window.scrollTo(0, scrollPosition);
            })
            .catch(error => {
                console.error('Fehler beim Setzen der Flagge:', error);
                window.location.href = flagHref;
            });
            // Statt nur Teile zu ersetzen, besser den gesamten Body ersetzen
        }
    });

    // Verhindert Textauswahl beim Rechtsklick
    document.addEventListener('selectstart', function(e) {
        if (e.target.classList.contains('cell')) {
            e.preventDefault();
        }
    });
});
