// assets/i18n.js
const I18N = {
  LANG_KEY: "site_lang",
  CACHE_KEY: "site_i18n_cache_v1",

  getLang() {
    return localStorage.getItem(this.LANG_KEY) || "ZH";
  },
  setLang(lang) {
    localStorage.setItem(this.LANG_KEY, String(lang || "ZH").toUpperCase());
  },

  loadCache() {
    try { return JSON.parse(localStorage.getItem(this.CACHE_KEY) || "{}"); }
    catch { return {}; }
  },
  saveCache(cache) {
    localStorage.setItem(this.CACHE_KEY, JSON.stringify(cache));
  },

  collectNodes() {
    //文字节点data-i18n
    //属性节点data-i18n-attr="placeholder|title|aria-label|alt"
    return Array.from(document.querySelectorAll("[data-i18n], [data-i18n-attr]"));
  },

  ensureSource(nodes) {
    nodes.forEach((el) => {
      //文本
      if (el.dataset.i18n && !el.dataset.src) {
        el.dataset.src = (el.textContent || "").trim();
      }
      //属性
      if (el.dataset.i18nAttr && !el.dataset.srcAttr) {
        const attr = el.dataset.i18nAttr;
        el.dataset.srcAttr = (el.getAttribute(attr) || "").trim();
      }
    });
  },

  apply(nodes, lang, cache) {
    nodes.forEach((el) => {
      //文本翻译
      if (el.dataset.i18n) {
        const key = el.dataset.i18n;
        const src = el.dataset.src || "";
        const hit = cache?.[lang]?.[key];
        el.textContent = hit || src;
      }
      //属性翻译
      if (el.dataset.i18nAttr) {
        const key = el.dataset.i18nKeyAttr || el.dataset.i18nAttr;
        const attr = el.dataset.i18nAttr;
        const src = el.dataset.srcAttr || "";
        const hit = cache?.[lang]?.[key];
        el.setAttribute(attr, hit || src);
      }
    });
  },

  async fetchMissing(nodes, lang, cache) {
    if (lang === "ZH") return cache;
    cache[lang] = cache[lang] || {};

    const texts = [];
    const keys = [];

    nodes.forEach((el) => {
      //文本缺失
      if (el.dataset.i18n) {
        const key = el.dataset.i18n;
        const src = el.dataset.src || "";
        if (src && !cache[lang][key]) {
          texts.push(src);
          keys.push(key);
        }
      }
      //属性缺失
      if (el.dataset.i18nAttr) {
        const key = el.dataset.i18nKeyAttr || el.dataset.i18nAttr;
        const src = el.dataset.srcAttr || "";
        if (src && !cache[lang][key]) {
          texts.push(src);
          keys.push(key);
        }
      }
    });

    if (texts.length === 0) return cache;

        const form = new URLSearchParams();
        form.set("target_lang", lang);
        texts.forEach(t => form.append("texts[]", t));

        const resp = await fetch("/api/translate.php", {
         method: "POST",
         headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" },
        body: form.toString()
        });


    if (!resp.ok) {
      console.error("translate.php failed:", await resp.text());
      return cache;
    }

    const data = await resp.json();
    const translated = data.translations || [];

    translated.forEach((t, i) => {
      cache[lang][keys[i]] = t;
    });

    this.saveCache(cache);
    return cache;
  },

  async init() {
    const lang = this.getLang();
    const nodes = this.collectNodes();
    this.ensureSource(nodes);

    let cache = this.loadCache();
    this.apply(nodes, lang, cache);

    cache = await this.fetchMissing(nodes, lang, cache);
    this.apply(nodes, lang, cache);

    document.documentElement.lang = lang.toLowerCase();
  }
};

document.addEventListener("DOMContentLoaded", () => {
  I18N.init();
});
