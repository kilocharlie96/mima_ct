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
})();
