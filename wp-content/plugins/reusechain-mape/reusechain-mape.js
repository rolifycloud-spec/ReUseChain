/**
 * Single unified typeInSearchField
 *  - Clears input
 *  - Sets value all at once (no character-by-character loop)
 *  - Dispatches input/change
 */
function typeInSearchField(element, text, callback) {
  element.value = text; // Single assignment
  element.dispatchEvent(new Event("input", { bubbles: true }));
  element.dispatchEvent(new Event("change", { bubbles: true }));

  // Optionally wait, then run callback:
  if (callback) {
    setTimeout(callback, 200);
  }
}

/**
 * Clears existing suggestions & triggers reset (if needed),
 * then types new input and auto-clicks the first suggestion.
 */
function searchCoordinatesOnMap(lat, lng) {
  // Optionally reset the map before searching
  const resetBtn = document.querySelector(".jet-fb-map-field__reset");
  if (resetBtn) {
    resetBtn.click();
  }

  const inputField = document.querySelector("div input[placeholder='Search...']");
  const suggestionList = document.querySelector(".jet-fb-map-field__search-list");
  if (!inputField) return;

  // Clear suggestions
  if (suggestionList) {
    suggestionList.innerHTML = "";
  }

  // Compose the coordinate string
  const coordinates = `${lat}, ${lng}`;

  // Type the coordinate string all at once
  typeInSearchField(inputField, coordinates, () => {
    // Attempt to click the first suggestion after the plugin populates
    waitForSearchResultsAndClick(10, 1000);
  });
}

// Existing wait function for suggestions
function waitForSearchResultsAndClick(retries, delay) {
  if (retries === 0) {
    console.error("No search item found after multiple retries.");
    return;
  }
  setTimeout(() => {
    const suggestionList = document.querySelector(".jet-fb-map-field__search-list");
    if (suggestionList) {
      const firstItem = suggestionList.querySelector("li.jet-fb-map-field__search-item");
      if (firstItem) {
        firstItem.click(); // This places the pin
        console.log("Clicked on:", firstItem.textContent);
        return;
      }
    }
    console.warn("Search item not found yet, retrying...");
    waitForSearchResultsAndClick(retries - 1, delay);
  }, delay);
}

/**
 * Use current location
 */
function getLocation() {
  if (!navigator.geolocation) {
    console.error("Geolocation is not supported by this browser.");
    return;
  }
  navigator.geolocation.getCurrentPosition(
    position => {
      // Round or fix your coords
      const lat = position.coords.latitude.toFixed(7);
      const lng = position.coords.longitude.toFixed(7);

      // Now just feed them to your map search routine
      searchCoordinatesOnMap(lat, lng);
    },
    error => {
      // Handle geolocation errors
      switch (error.code) {
        case error.PERMISSION_DENIED:
          console.error("User denied the request for Geolocation.");
          break;
        case error.POSITION_UNAVAILABLE:
          console.error("Location information is unavailable.");
          break;
        case error.TIMEOUT:
          console.error("The request to get user location timed out.");
          break;
        case error.UNKNOWN_ERROR:
          console.error("An unknown error occurred.");
          break;
      }
    }
  );
}

function forceClearMarkers() { 
  // Remove all marker <div> elements
  const markerPane = document.querySelector(".leaflet-marker-pane");
  if (markerPane) {
    markerPane.innerHTML = "";
  }

  // Optionally clear out the hidden lat/lng fields
  const latHidden = document.querySelector("[data-map-field='lat']");
  const lngHidden = document.querySelector("[data-map-field='lng']");
  if (latHidden) latHidden.value = "";
  if (lngHidden) lngHidden.value = "";
}

function handleGeoButtonsVisibility() {

    const regijaDropdown = document.getElementById("regija");
    const mjestoDropdown = document.getElementById("mjesto");
    const wrapper = document.querySelector(".pretraga_lokacije")?.parentElement;

    if (!regijaDropdown || !mjestoDropdown || !wrapper) return;

    function toggleButtons() {

        // REGIJA CHECK
        const selectedRegija = regijaDropdown.options[regijaDropdown.selectedIndex];
        const regijaValid =
            selectedRegija &&
            !selectedRegija.disabled &&
            selectedRegija.value !== "0";

        // MJESTO CHECK
        const mjestoValid =
            mjestoDropdown.value &&
            mjestoDropdown.value !== "" &&
            mjestoDropdown.options.length > 1; // mora imati više od default opcije

        wrapper.style.display = (regijaValid && mjestoValid) ? "flex" : "none";
    }

    // Initial state
    toggleButtons();

    // Listen to both dropdowns
    regijaDropdown.addEventListener("change", toggleButtons);
    mjestoDropdown.addEventListener("change", toggleButtons);
}



document.addEventListener("DOMContentLoaded", () => {    
	
	const KEY_ELEMENTS = [
        "#regija",
        "#mjesto",
        "input[name='entitet']",
        "#ulica_i_broj",
        "div input[placeholder='Search...']",
        ".pretraga_lokacije",
        "#rezultati-geoAPI",
        ".jet-fb-map-field__position"
    ];

    // Ako NIJEDAN od ključnih elemenata ne postoji → stranica nije relevantna za skriptu
    const hasRelevantElements = KEY_ELEMENTS.some(sel => document.querySelector(sel));

    if (!hasRelevantElements) {
        console.warn("Upravljanje oglasom: Nema relevantnih elemenata — skripta deaktivirana.");
        return;
    }else{
handleGeoButtonsVisibility();
    console.log("Reusechain mape: Aktivno — elementi su pronađeni.");}

    /*******************************
     * 1) ENTITET → REGIJA → MJESTO
     *******************************/
    (function initEntitetRegijaMjesto() {

        const entitetInput = document.querySelector('input[name="entitet"]');
        const regijaDropdown = document.getElementById("regija");
        const mjestoDropdown = document.getElementById("mjesto");

        if (!entitetInput || !regijaDropdown || !mjestoDropdown) return; // ❗ TIHI EXIT

        const mjestoLabel = mjestoDropdown.closest('.jet-form-builder-row')
            ?.querySelector('.jet-form-builder__label-text');

        if (!mjestoLabel) return;

        const jsonUrl = reusechainMapeVars?.json_url;
        if (!jsonUrl) return;

        fetch(jsonUrl)
            .then(r => r.json())
            .then(data => {

                // Popuni regije
                Object.entries(data).forEach(([entitet, regije]) => {
                    const hdr = new Option(entitet, "disabled");
                    hdr.disabled = true;
                    regijaDropdown.appendChild(hdr);

                    Object.keys(regije).forEach(regija => {
                        const opt = new Option(regija, regija);
                        opt.dataset.entitet = entitet;
                        regijaDropdown.appendChild(opt);
                    });
                });

                // Promjena regije
                regijaDropdown.addEventListener("change", function () {
                    mjestoDropdown.innerHTML = '<option value="">Odaberite mjesto</option>';

                    const selectedEntitet = this.options[this.selectedIndex]?.dataset.entitet;
                    const selectedRegija = this.value;

                    entitetInput.value = selectedEntitet || "";

                    if (!selectedEntitet || !selectedRegija) return;

                    const mjesta = data[selectedEntitet]?.[selectedRegija];
                    if (!mjesta) return;

                    mjesta.forEach(mjesto => {
                        const name = typeof mjesto === "string" ? mjesto : mjesto.name;
                        mjestoDropdown.add(new Option(name, name));
                    });

                    // Prefill mjesto
                    const defaultMjesto = mjestoDropdown.dataset.defaultVal;
                    if (defaultMjesto) {
                        const exists = [...mjestoDropdown.options].some(opt => opt.value === defaultMjesto);
                        if (exists) {
                            mjestoDropdown.value = defaultMjesto;
                            mjestoDropdown.dispatchEvent(new Event("change"));
                        }
                    }
                });

                // Prefill regija
                const defaultRegija = regijaDropdown.dataset.defaultVal;
                if (defaultRegija) {
                    regijaDropdown.value = defaultRegija;
                    regijaDropdown.dispatchEvent(new Event("change"));
                }
            });
    })();



    /*******************************
     * 2) JETENGINE MAPA + OSM
     *******************************/
    (function initGeoSearch() {

        const placeInput = document.getElementById("mjesto");
        const streetInput = document.getElementById("ulica_i_broj");
        const mapSearchInput = document.querySelector("div input[placeholder='Search...']");
        const searchParagraph = document.querySelector(".pretraga_lokacije");
        const resultParagraph = document.getElementById("rezultati-geoAPI");

        if (!placeInput || !streetInput || !mapSearchInput || !searchParagraph || !resultParagraph) return;

        searchParagraph.addEventListener("click", function () {
            const street = streetInput.value.trim();
            const place = placeInput.options[placeInput.selectedIndex]?.text?.trim();

            if (!place || place === "Odaberite mjesto") {
                resultParagraph.textContent = "❌ Molimo unesite mjesto.";
                return;
            }

            function attemptSearch(streetQuery, fallback = true) {

                const q = encodeURIComponent(`${streetQuery}, ${place}, Bosnia and Herzegovina`);
                const url = `https://nominatim.openstreetmap.org/search?q=${q}&format=json&limit=1`;

                fetch(url)
                    .then(r => r.json())
                    .then(data => {

                        if (data.length > 0) {
                            const lat = parseFloat(data[0].lat);
                            const lng = parseFloat(data[0].lon);
                            searchCoordinatesOnMap(lat, lng);
                            resultParagraph.textContent = "";
                        }
                        else if (fallback && streetQuery) {
                            attemptSearch("", false);
                        }
                        else {
                            resultParagraph.textContent = "❌ Lokacija nije pronađena.";
                        }
                    });
            }

            attemptSearch(street);
        });

    })();

});
