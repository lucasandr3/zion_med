/**
 * Loader global para requisições.
 * Integrado ao Axios; também expõe showLoader/hideLoader para uso manual (ex.: fetch).
 */

let pendingCount = 0;
let loaderEl = null;

function getLoader() {
  if (!loaderEl) loaderEl = document.getElementById('global-loader');
  return loaderEl;
}

function showLoader() {
  const el = getLoader();
  if (!el) return;
  pendingCount++;
  el.classList.add('is-active');
  el.setAttribute('aria-hidden', 'false');
}

function hideLoader() {
  const el = getLoader();
  if (!el) return;
  pendingCount = Math.max(0, pendingCount - 1);
  if (pendingCount === 0) {
    el.classList.remove('is-active');
    el.setAttribute('aria-hidden', 'true');
  }
}

/**
 * Configura interceptors do Axios para mostrar/ocultar o loader automaticamente.
 * Requisições com config.skipLoader === true não disparam o loader.
 */
function setupAxiosLoader(axiosInstance) {
  if (!axiosInstance || !axiosInstance.interceptors) return;

  const reqId = axiosInstance.interceptors.request.use(
    (config) => {
      if (config.skipLoader) return config;
      showLoader();
      config._loaderShown = true;
      return config;
    },
    (err) => Promise.reject(err)
  );

  axiosInstance.interceptors.response.use(
    (response) => {
      if (response.config && response.config._loaderShown) hideLoader();
      return response;
    },
    (error) => {
      if (error.config && error.config._loaderShown) hideLoader();
      return Promise.reject(error);
    }
  );
}

// Integra ao axios global assim que estiver disponível
if (typeof window !== 'undefined') {
  window.showLoader = showLoader;
  window.hideLoader = hideLoader;

  if (window.axios) {
    setupAxiosLoader(window.axios);
  } else {
    window.addEventListener('load', () => {
      if (window.axios) setupAxiosLoader(window.axios);
    });
  }
}

export { showLoader, hideLoader, setupAxiosLoader };
