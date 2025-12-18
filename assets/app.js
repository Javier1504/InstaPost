window.UI = (() => {
  const toastEl = () => document.getElementById("toast");

  function toast(title, desc){
    const el = toastEl();
    if(!el) return alert(title + "\n" + (desc||""));
    el.querySelector(".t").textContent = title;
    el.querySelector(".d").textContent = desc || "";
    el.classList.add("show");
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove("show"), 2600);
  }

  async function postJSON(url, formData){
    const res = await fetch(url, { method:"POST", body: formData });
    const text = await res.text();
    try { return JSON.parse(text); }
    catch { throw new Error(text); }
  }

  return { toast, postJSON };
})();
