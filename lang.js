(function () {
  var COOKIE = "mima_lang";
  var pageLang = (document.documentElement.lang || "sk").toLowerCase();

  function setLangCookie(lang) {
    document.cookie =
      COOKIE +
      "=" +
      encodeURIComponent(lang) +
      ";path=/;max-age=31536000;SameSite=Lax";
  }

  if (["sk", "en", "de"].indexOf(pageLang) !== -1) {
    setLangCookie(pageLang);
  }

  document.querySelectorAll("a[data-lang]").forEach(function (link) {
    link.addEventListener("click", function () {
      var lang = link.getAttribute("data-lang");
      if (lang) {
        setLangCookie(lang);
      }
    });
  });

  var contactStatus = new URLSearchParams(window.location.search).get("contact");
  var messages = {
    sk: {
      sent: "Ďakujeme, váš dopyt bol odoslaný.",
      error: "Dopyt sa nepodarilo odoslať. Skúste to, prosím, neskôr.",
      invalid: "Skontrolujte, prosím, zadanú e-mailovú adresu a skúste to znova.",
    },
    en: {
      sent: "Thank you, your enquiry has been sent.",
      error: "We could not send your enquiry. Please try again later.",
      invalid: "Please check the email address and try again.",
    },
    de: {
      sent: "Vielen Dank, Ihre Anfrage wurde gesendet.",
      error: "Ihre Anfrage konnte nicht gesendet werden. Bitte versuchen Sie es später erneut.",
      invalid: "Bitte überprüfen Sie die E-Mail-Adresse und versuchen Sie es erneut.",
    },
  };

  if (contactStatus && messages[pageLang] && messages[pageLang][contactStatus]) {
    var form = document.querySelector(".contact-box form");
    if (form) {
      var notice = document.createElement("div");
      notice.className =
        "notification " + (contactStatus === "sent" ? "is-success" : "is-danger");
      notice.setAttribute("role", "status");
      notice.setAttribute("aria-live", "polite");
      notice.textContent = messages[pageLang][contactStatus];
      form.parentNode.insertBefore(notice, form);
    }

    window.history.replaceState(
      null,
      "",
      window.location.pathname + window.location.hash
    );
  }
})();
