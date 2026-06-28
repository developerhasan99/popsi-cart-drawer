(function () {
  "use strict";

  const PopsiCart = {
    isOpen: false,
    isLoading: false,
    cartCount: 0,
    timerCount: 0,
    timerInterval: null,
    debounceTimer: null,
    refreshTimer: null,
    isUpdatingUI: false,
    selectedVariations: {},
    selectedVariationAttributes: {},
    upsellPrices: {},
    upsellVariations: {},

    getUpsellItem(productId) {
      return document.querySelector(`.bc-upsell-item[data-id="${productId}"]`);
    },

    getUpsellAttributeSelects(productId) {
      const item = this.getUpsellItem(productId);
      if (!item) return [];

      return Array.from(
        item.querySelectorAll(`.bc-upsell-select[data-product-id="${productId}"]`),
      );
    },

    collectUpsellAttributeSelections(productId) {
      const selections = {};

      this.getUpsellAttributeSelects(productId).forEach((select) => {
        selections[select.dataset.attributeKey] = select.value || "";
      });

      return selections;
    },

    parseUpsellVariations(item) {
      if (!item || !item.dataset.variations) return [];

      try {
        const variations = JSON.parse(item.dataset.variations);
        return Array.isArray(variations) ? variations : [];
      } catch (error) {
        console.log("Error parsing variation data:", error);
        return [];
      }
    },

    isVariationMatch(variationAttributes, selectedAttributes) {
      return Object.keys(selectedAttributes).every((attrKey) => {
        const selectedValue = selectedAttributes[attrKey] || "";
        if (!selectedValue) return true;

        const variationValue = variationAttributes[attrKey] || "";
        return !variationValue || variationValue === selectedValue;
      });
    },

    findMatchingVariation(productId, selectedAttributes) {
      const variations = this.upsellVariations[productId] || [];

      return (
        variations.find((variation) => {
          if (!variation || !variation.attributes) return false;

          return this.isVariationMatch(variation.attributes, selectedAttributes);
        }) || null
      );
    },

    updateUpsellPrice(productId, variationId = "") {
      const item = this.getUpsellItem(productId);
      if (!item) return;

      const priceDisplay = item.querySelector(".bc-upsell-price");
      if (!priceDisplay) return;

      if (
        priceDisplay &&
        this.upsellPrices[productId] &&
        variationId &&
        this.upsellPrices[productId][variationId]
      ) {
        priceDisplay.innerHTML = this.upsellPrices[productId][variationId];
      }
    },

    updateUpsellAddButton(productId, hasMatch, isComplete) {
      const item = this.getUpsellItem(productId);
      if (!item) return;

      const button = item.querySelector(".bc-upsell-add");
      if (!button) return;

      button.disabled = !hasMatch || !isComplete;
      button.style.opacity = !hasMatch || !isComplete ? "0.6" : "";
      button.style.cursor = !hasMatch || !isComplete ? "not-allowed" : "";
    },

    updateUpsellOptionAvailability(productId) {
      const variations = this.upsellVariations[productId] || [];
      const selects = this.getUpsellAttributeSelects(productId);
      const currentSelections = this.collectUpsellAttributeSelections(productId);

      selects.forEach((select) => {
        const attributeKey = select.dataset.attributeKey;

        Array.from(select.options).forEach((option) => {
          if (!option.value) {
            option.disabled = false;
            return;
          }

          const candidateSelections = {
            ...currentSelections,
            [attributeKey]: option.value,
          };

          option.disabled = !variations.some((variation) =>
            this.isVariationMatch(variation.attributes || {}, candidateSelections),
          );
        });
      });
    },

    syncUpsellVariation(productId) {
      const selectedAttributes = this.collectUpsellAttributeSelections(productId);
      const hasEmptySelection = Object.values(selectedAttributes).some(
        (value) => !value,
      );

      this.selectedVariationAttributes[productId] = selectedAttributes;
      this.updateUpsellOptionAvailability(productId);

      if (hasEmptySelection) {
        this.selectedVariations[productId] = "";
        this.updateUpsellAddButton(productId, false, false);
        return;
      }

      const matchedVariation = this.findMatchingVariation(
        productId,
        selectedAttributes,
      );

      this.selectedVariations[productId] = matchedVariation
        ? String(matchedVariation.id)
        : "";

      this.updateUpsellPrice(
        productId,
        matchedVariation ? String(matchedVariation.id) : "",
      );
      this.updateUpsellAddButton(productId, Boolean(matchedVariation), true);
    },

    init() {
      const settings = popsiCartData.settings || {};
      const initialMinutes = parseInt(settings.timer_duration) || 15;
      this.timerCount = initialMinutes * 60;

      this.cacheDOM();
      this.bindEvents();
      this.initWooCommerceEvents();
      this.initDynamicItems();
      this.updateHeaderBubble();

      // Fetch fresh data immediately on page load to bypass caching
      this.getCart();

      // Auto-open on page load if a product was just added (for non-AJAX themes/refreshes)
      if (
        document.cookie
          .split(";")
          .some((item) => item.trim().startsWith("popsi_cart_just_added="))
      ) {
        // Clear the cookie immediately
        document.cookie =
          "popsi_cart_just_added=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";

        // Only open if the setting allows
        if (settings.enable_cart_drawer && settings.auto_open_cart) {
          this.openCart(true);
        }
      }
    },

    cacheDOM() {
      this.drawerWrap = document.querySelector(".bc-drawer-wrap");
      this.drawerBody = document.querySelector(".bc-drawer-body");
      this.cartHtmlContainer = document.querySelector(
        ".bc-cart-html-container",
      );
      this.loadingOverlay = document.querySelector(".bc-loading-overlay");
      this.countBubbles = document.querySelectorAll(".popsi-cart-count-bubble");
      this.cartCountDisplay = document.querySelector(".bc-cart-count-display");
      this.announcementBar = document.querySelector(".bc-announcement");
    },

    bindEvents() {
      // Open cart event
      window.addEventListener("open-popsi-cart", () => this.openCart());

      // Close icons/overlay
      document.addEventListener("click", (e) => {
        if (
          e.target.closest(".bc-drawer-close") ||
          e.target.closest(".bc-empty-cart-btn") ||
          e.target.classList.contains("bc-overlay")
        ) {
          this.closeCart();
        }
      });

      // Delegate actions inside drawer
      if (this.drawerWrap) {
        this.drawerWrap.addEventListener("click", (e) => {
          const target = e.target;

          // Remove item or update qty
          if (target.closest(".bc-item-remove")) {
            const btn = target.closest(".bc-item-remove");
            this.updateItem(btn.dataset.key, 0);
          }
          if (target.closest(".bc-qty-btn")) {
            const btn = target.closest(".bc-qty-btn");
            const key = btn.dataset.key;
            const qty = parseInt(btn.dataset.qty);
            this.updateItem(key, qty);
          }

          // Apply coupon
          if (target.closest(".bc-coupon-btn")) {
            const input = this.drawerWrap.querySelector(".bc-coupon-input");
            if (input) this.applyCoupon(input.value);
          }

          // Remove coupon
          if (target.closest(".bc-badge-remove")) {
            const btn = target.closest(".bc-badge-remove");
            this.removeCoupon(btn.dataset.code);
          }

          // Add upsell
          if (target.closest(".bc-upsell-add")) {
            const btn = target.closest(".bc-upsell-add");
            this.addUpsell(btn.dataset.id);
          }

          // Coupon accordion toggle
          if (target.closest(".bc-coupon-toggle")) {
            const accordion = target.closest(".bc-coupon-accordion");
            const content = accordion.querySelector(
              ".bc-coupon-accordion-content",
            );
            const icon = accordion.querySelector(".bc-coupon-toggle-icon");

            if (content.style.maxHeight && content.style.maxHeight !== "0px") {
              content.style.maxHeight = "0px";
              icon.classList.remove("is-open");
            } else {
              content.style.maxHeight = content.scrollHeight + "px";
              icon.classList.add("is-open");
            }
          }
        });

        // Variation select change
        this.drawerWrap.addEventListener("change", (e) => {
          if (e.target.classList.contains("bc-upsell-select")) {
            const select = e.target;
            const productId = select.dataset.productId;
            this.syncUpsellVariation(productId);
          }
        });
      }

      // Global trigger icons
      document.querySelectorAll(".bc-icon-trigger").forEach((el) => {
        el.addEventListener("click", (e) => {
          e.preventDefault();
          this.openCart();
        });
      });
    },

    initWooCommerceEvents() {
      if (typeof jQuery !== "undefined") {
        const $ = jQuery;
        $(document.body).on(
          "added_to_cart removed_from_cart updated_cart_totals",
          (event) => {
            // Clear any "just added" cookie if an AJAX add-to-cart just happened
            if (event.type === "added_to_cart") {
              document.cookie =
                "popsi_cart_just_added=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            }

            if (this.isUpdatingUI) return;
            const settings = popsiCartData.settings || {};
            if (this.isOpen) {
              this.getCart();
            } else if (
              settings.enable_cart_drawer &&
              settings.auto_open_cart &&
              event.type === "added_to_cart"
            ) {
              this.openCart(true);
            } else {
              this.getCart();
            }
          },
        );
      }
    },

    openCart(forceRefresh = false) {
      if (this.isOpen && !forceRefresh) return;

      this.isOpen = true;
      if (this.drawerWrap) this.drawerWrap.classList.add("is-open");
      document.body.style.overflow = "hidden";

      if (forceRefresh) {
        this.getCart();
      }

      this.startTimer();
    },

    closeCart() {
      this.isOpen = false;
      if (this.drawerWrap) this.drawerWrap.classList.remove("is-open");
      document.body.style.overflow = "";
    },

    setLoading(loading) {
      this.isLoading = loading;
      if (this.drawerBody) {
        if (loading) this.drawerBody.classList.add("is-loading");
        else this.drawerBody.classList.remove("is-loading");
      }
      if (this.loadingOverlay) {
        this.loadingOverlay.style.display = loading ? "flex" : "none";
      }
    },

    async getCart() {
      if (this.isLoading) return;
      this.setLoading(true);
      try {
        const formData = new FormData();
        formData.append("action", "popsi_cart_get_cart");
        formData.append("security", popsiCartData.nonce);

        const response = await fetch(popsiCartData.ajax_url, {
          method: "POST",
          body: formData,
        });
        const res = await response.json();
        if (res.success) {
          this.updateCartUI(res.data);
        }
      } catch (error) {
        console.error(error);
      } finally {
        this.setLoading(false);
      }
    },

    updateCartUI(data) {
      this.isUpdatingUI = true;
      if (this.cartHtmlContainer) {
        this.cartHtmlContainer.innerHTML = data.html;
      }
      this.cartCount = data.count;
      this.updateHeaderBubble();
      this.initDynamicItems();
      this.triggerGlobalRefresh(true); // Always immediate for better responsiveness
      setTimeout(() => {
        this.isUpdatingUI = false;
      }, 1000);
    },

    triggerGlobalRefresh(immediate = false) {
      if (typeof jQuery !== "undefined") {
        if (this.refreshTimer) clearTimeout(this.refreshTimer);
        if (immediate) {
          jQuery(document.body).trigger("wc_fragment_refresh");
        } else {
          this.refreshTimer = setTimeout(() => {
            jQuery(document.body).trigger("wc_fragment_refresh");
          }, 2000);
        }
      }
    },

    initDynamicItems() {
      const upsellItems = document.querySelectorAll(
        ".bc-upsell-item[data-id]",
      );
      upsellItems.forEach((item) => {
        const id = item.dataset.id;
        try {
          this.upsellPrices[id] = item.dataset.prices
            ? JSON.parse(item.dataset.prices)
            : {};
          this.upsellVariations[id] = this.parseUpsellVariations(item);

          const variationSelects = this.getUpsellAttributeSelects(id);
          if (variationSelects.length) {
            this.syncUpsellVariation(id);
          } else {
            this.selectedVariations[id] = "";
          }
        } catch (e) {}
      });
    },

    async updateItem(key, qty) {
      if (qty < 0) return;
      this.setLoading(true);
      try {
        const formData = new FormData();
        formData.append("action", "popsi_cart_update_item");
        formData.append("security", popsiCartData.nonce);
        formData.append("cart_item_key", key);
        formData.append("quantity", qty);

        const response = await fetch(popsiCartData.ajax_url, {
          method: "POST",
          body: formData,
        });
        const res = await response.json();
        if (res.success) {
          this.updateCartUI(res.data);
        }
      } catch (error) {
        console.error(error);
      } finally {
        this.setLoading(false);
      }
    },

    async addUpsell(id) {
      if (this.isLoading) return;
      this.setLoading(true);
      try {
        const formData = new FormData();
        formData.append("action", "popsi_cart_add_to_cart");
        formData.append("security", popsiCartData.nonce);
        formData.append("product_id", id);
        formData.append("quantity", 1);

        if (this.selectedVariations[id]) {
          formData.append("variation_id", this.selectedVariations[id]);
        }

        if (this.selectedVariationAttributes[id]) {
          Object.keys(this.selectedVariationAttributes[id]).forEach((attrKey) => {
            const attrValue = this.selectedVariationAttributes[id][attrKey];
            if (!attrValue) return;

            formData.append(attrKey, String(attrValue));
          });
        } else if ((this.upsellVariations[id] || []).length > 0) {
          this.setLoading(false);
          return;
        }

        const response = await fetch(popsiCartData.ajax_url, {
          method: "POST",
          body: formData,
        });
        const res = await response.json();
        if (res.success) {
          this.updateCartUI(res.data);
        } else {
          console.log("Upsell add to cart failed");

          // Log detailed error messages from WooCommerce
          if (
            res.data &&
            res.data.messages &&
            Array.isArray(res.data.messages)
          ) {
            console.log("WooCommerce error messages:");
            res.data.messages.forEach((message, index) => {
              console.log(`Error ${index + 1}:`, message);
            });
          } else if (res.data && typeof res.data === "string") {
            console.log("Error message:", res.data);
          } else {
            console.log("Unknown error occurred");
          }
        }
      } catch (error) {
        console.error(error);
      } finally {
        this.setLoading(false);
      }
    },

    async applyCoupon(code) {
      if (!code) return;
      this.setLoading(true);
      try {
        const formData = new FormData();
        formData.append("action", "popsi_cart_apply_coupon");
        formData.append("security", popsiCartData.nonce);
        formData.append("coupon", code);

        const response = await fetch(popsiCartData.ajax_url, {
          method: "POST",
          body: formData,
        });
        const res = await response.json();
        if (res.success) {
          this.updateCartUI(res.data);
        }
      } catch (error) {
        console.error(error);
      } finally {
        this.setLoading(false);
      }
    },

    async removeCoupon(code) {
      if (!code) return;
      this.setLoading(true);
      try {
        const formData = new FormData();
        formData.append("action", "popsi_cart_remove_coupon");
        formData.append("security", popsiCartData.nonce);
        formData.append("coupon", code);

        const response = await fetch(popsiCartData.ajax_url, {
          method: "POST",
          body: formData,
        });
        const res = await response.json();
        if (res.success) {
          this.updateCartUI(res.data);
        }
      } catch (error) {
        console.error(error);
      } finally {
        this.setLoading(false);
      }
    },

    updateHeaderBubble() {
      const count = parseInt(this.cartCount) || 0;

      // Re-query bubbles in case WooCommerce fragments replaced them
      const bubbles = document.querySelectorAll(".popsi-cart-count-bubble");
      bubbles.forEach((b) => {
        b.textContent = count;
        b.style.display = count > 0 ? "flex" : "none";
      });

      // Re-query the drawer-specific count display
      const countDisplay = document.querySelector(".bc-cart-count-display");
      if (countDisplay) {
        countDisplay.textContent = count;
      }
    },

    startTimer() {
      if (this.timerInterval) return;
      this.timerInterval = setInterval(() => {
        if (this.timerCount > 0) {
          this.timerCount--;
          this.updateTimerDisplay();
        } else {
          this.stopTimer();
        }
      }, 1000);
    },

    stopTimer() {
      clearInterval(this.timerInterval);
      this.timerInterval = null;
    },

    updateTimerDisplay() {
      if (this.announcementBar) {
        const timerStrong =
          this.announcementBar.querySelector(".bc-timer-bold");
        if (timerStrong) {
          timerStrong.textContent = this.formatTime(this.timerCount);
        }
      }
    },

    formatTime(seconds) {
      const m = Math.floor(seconds / 60);
      const s = seconds % 60;
      return (m < 10 ? "0" + m : m) + ":" + (s < 10 ? "0" + s : s);
    },
  };

  document.addEventListener("DOMContentLoaded", () => PopsiCart.init());
})();
