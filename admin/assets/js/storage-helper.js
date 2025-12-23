/**
 * Storage Helper - Gracefully handles browser tracking prevention
 * Wraps localStorage/sessionStorage access with fallbacks
 */
(function(window) {
    'use strict';

    // Test if storage is available
    function storageAvailable(type) {
        try {
            const storage = window[type];
            const testKey = '__storage_test__';
            storage.setItem(testKey, testKey);
            storage.removeItem(testKey);
            return true;
        } catch (e) {
            return false;
        }
    }

    const hasLocalStorage = storageAvailable('localStorage');
    const hasSessionStorage = storageAvailable('sessionStorage');

    // In-memory fallback for when storage is blocked
    const memoryStorage = {
        _data: {},
        setItem: function(key, value) {
            this._data[key] = String(value);
        },
        getItem: function(key) {
            return this._data.hasOwnProperty(key) ? this._data[key] : null;
        },
        removeItem: function(key) {
            delete this._data[key];
        },
        clear: function() {
            this._data = {};
        }
    };

    // Safe wrappers
    window.safeLocalStorage = {
        setItem: function(key, value) {
            try {
                if (hasLocalStorage) {
                    localStorage.setItem(key, value);
                } else {
                    memoryStorage.setItem(key, value);
                }
            } catch (e) {
                console.warn('Storage blocked:', e.message);
                memoryStorage.setItem(key, value);
            }
        },
        getItem: function(key) {
            try {
                if (hasLocalStorage) {
                    return localStorage.getItem(key);
                } else {
                    return memoryStorage.getItem(key);
                }
            } catch (e) {
                console.warn('Storage blocked:', e.message);
                return memoryStorage.getItem(key);
            }
        },
        removeItem: function(key) {
            try {
                if (hasLocalStorage) {
                    localStorage.removeItem(key);
                } else {
                    memoryStorage.removeItem(key);
                }
            } catch (e) {
                console.warn('Storage blocked:', e.message);
                memoryStorage.removeItem(key);
            }
        },
        clear: function() {
            try {
                if (hasLocalStorage) {
                    localStorage.clear();
                } else {
                    memoryStorage.clear();
                }
            } catch (e) {
                console.warn('Storage blocked:', e.message);
                memoryStorage.clear();
            }
        }
    };

    window.safeSessionStorage = {
        setItem: function(key, value) {
            try {
                if (hasSessionStorage) {
                    sessionStorage.setItem(key, value);
                } else {
                    memoryStorage.setItem('session_' + key, value);
                }
            } catch (e) {
                console.warn('Storage blocked:', e.message);
                memoryStorage.setItem('session_' + key, value);
            }
        },
        getItem: function(key) {
            try {
                if (hasSessionStorage) {
                    return sessionStorage.getItem(key);
                } else {
                    return memoryStorage.getItem('session_' + key);
                }
            } catch (e) {
                console.warn('Storage blocked:', e.message);
                return memoryStorage.getItem('session_' + key);
            }
        },
        removeItem: function(key) {
            try {
                if (hasSessionStorage) {
                    sessionStorage.removeItem(key);
                } else {
                    memoryStorage.removeItem('session_' + key);
                }
            } catch (e) {
                console.warn('Storage blocked:', e.message);
                memoryStorage.removeItem('session_' + key);
            }
        },
        clear: function() {
            try {
                if (hasSessionStorage) {
                    sessionStorage.clear();
                } else {
                    // Clear only session items from memory storage
                    Object.keys(memoryStorage._data).forEach(key => {
                        if (key.startsWith('session_')) {
                            delete memoryStorage._data[key];
                        }
                    });
                }
            } catch (e) {
                console.warn('Storage blocked:', e.message);
            }
        }
    };

})(window);
