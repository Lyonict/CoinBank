import "./vendor/@hotwired/turbo/turbo.index.js";
import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './vendor/bootstrap/bootstrap.index.js';

import './form.js';

import { setCookie } from "./cookies.js";

function setLocaleInCookie(locale) {
  setCookie("CB-prefered-locale", locale, 365);
  window.location.href = window.location.pathname.replace(/^\/(en|fr)(\/|$)/, '/');
}

document.addEventListener("turbo:load", () => {
  // Add locale to cookie if user selects it
  const localeChangeSelect = document.querySelector("#CB-locale-change-select");
  if (localeChangeSelect) {
    localeChangeSelect.addEventListener("change", () => {
      setLocaleInCookie(localeChangeSelect.value);
    });
  }
});
