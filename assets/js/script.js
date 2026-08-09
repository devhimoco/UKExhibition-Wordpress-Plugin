(function ($) {
	'use strict';

	// True on devices with a real mouse/hover pointer (desktop).
	// False on touchscreens (phones, tablets) — where the hover-to-reveal
	// card overlay needs a tap-to-open fallback instead.
	const hasHoverSupport = window.matchMedia('(hover: hover)').matches;

	let currentPage        = 1;
	let currentSearch      = '';
	let currentPerPage     = 20; // default 20
	let selectedCategories = []; // multi-select array
	let searchTimer        = null;

	function loadExhibitions() {
		const $grid = $('#rhg-exh-grid');
		$grid.html('<p class="rhg-exh-loading">Loading exhibitions...</p>');

		$.post( rhgExhData.ajaxUrl, {
			action:   'rhg_exh_filter',
			nonce:    rhgExhData.nonce,
			search:   currentSearch,
			category: JSON.stringify( selectedCategories ),
			per_page: currentPerPage,
			page:     currentPage
		}).done(function (response) {
			if ( ! response.success ) {
				$grid.html('<p class="rhg-exh-empty">Something went wrong loading exhibitions.</p>');
				return;
			}
			$grid.html(response.data.html);
			renderPagination(response.data.total_pages, response.data.page);
		}).fail(function () {
			$grid.html('<p class="rhg-exh-empty">Could not load exhibitions. Please try again.</p>');
		});
	}

	function renderPagination(totalPages, page) {
		const $p = $('#rhg-exh-pagination');
		$p.empty();
		if ( totalPages <= 1 ) return;

		for ( let i = 1; i <= totalPages; i++ ) {
			const $btn = $('<button type="button">' + i + '</button>');
			if ( i === page ) $btn.addClass('active');
			$btn.on('click', function () {
				currentPage = i;
				loadExhibitions();
				$('html, body').animate({ scrollTop: $('#rhg-exh-categories').offset().top - 80 }, 250);
			});
			$p.append($btn);
		}
	}

	function updateCategoryUI() {
		$('.rhg-cat-pill').each(function () {
			const cat = $(this).data('category');
			if ( cat === '' ) {
				$(this).toggleClass('active', selectedCategories.length === 0);
			} else {
				$(this).toggleClass('active', selectedCategories.indexOf(cat) !== -1);
			}
		});

		if ( selectedCategories.length > 0 ) {
			$('#rhg-active-cat-name').text( selectedCategories.join(', ') );
			$('#rhg-exh-active-filter').show();
			$('#rhg-cat-toggle-count').text( ' (' + selectedCategories.length + ')' );
		} else {
			$('#rhg-exh-active-filter').hide();
			$('#rhg-cat-toggle-count').text( '' );
		}
	}

	function toggleCategory(cat) {
		if ( cat === '' ) {
			selectedCategories = [];
		} else {
			const idx = selectedCategories.indexOf(cat);
			if ( idx === -1 ) {
				selectedCategories.push(cat);
			} else {
				selectedCategories.splice(idx, 1);
			}
		}
		currentPage = 1;
		updateCategoryUI();
		loadExhibitions();
	}

	/* ============================================================
	   CUSTOM COMBO DROPDOWN WIDGET
	   Replaces native <datalist> entirely — datalist mixes browser
	   saved-form-data suggestions into the native dropdown (visible
	   as an inconsistent black list in Edge/Chrome). This widget is
	   fully custom-built and styled, showing ONLY our own data.
	   ============================================================ */

	// Returns the current option list for a given combo key ('job_title',
	// 'country', 'town'). Town depends on whichever country is currently
	// typed in the Country field.
	function getComboItems(comboKey) {
		if (comboKey === 'job_title') {
			return rhgExhData.jobTitles || [];
		}
		if (comboKey === 'country') {
			return rhgExhData.countries || [];
		}
		if (comboKey === 'town') {
			const countryVal = $('#rhg-country-input').val();
			if (!countryVal || !rhgExhData.countryCities) return [];
			const map = rhgExhData.countryCities;
			const matchKey = Object.keys(map).find(
				k => k.toLowerCase() === countryVal.toLowerCase()
			);
			return (matchKey && map[matchKey]) ? map[matchKey] : [];
		}
		return [];
	}

	function renderComboMenu($combo, filterText) {
		const comboKey = $combo.data('combo');
		const $menu    = $combo.find('.rhg-combo-menu');
		const items    = getComboItems(comboKey);
		const filter   = (filterText || '').toLowerCase().trim();

		const filtered = filter
			? items.filter(item => item.toLowerCase().indexOf(filter) !== -1)
			: items;

		$menu.empty();

		if (!items.length) {
			// e.g. Town with no country chosen yet
			const msg = comboKey === 'town' ? 'Select a country first' : 'No suggestions';
			$menu.append($('<li class="rhg-combo-empty">').text(msg));
		} else if (!filtered.length) {
			$menu.append($('<li class="rhg-combo-empty">').text('No matches — you can still type your own'));
		} else {
			filtered.slice(0, 60).forEach(function (item) {
				const $li = $('<li class="rhg-combo-item">').text(item);
				$li.on('mousedown', function (e) {
					// mousedown (not click) so it fires before the input's blur event
					e.preventDefault();
					$combo.find('.rhg-combo-input').val(item).trigger('rhg:comboSelect');
					closeComboMenu($combo);
				});
				$menu.append($li);
			});
		}

		$menu.addClass('open');
	}

	function closeComboMenu($combo) {
		$combo.find('.rhg-combo-menu').removeClass('open').empty();
	}

	function closeAllComboMenus() {
		$('.rhg-combo').each(function () { closeComboMenu($(this)); });
	}

	function initCombos() {
		// Open + filter on input
		$(document).on('input focus', '.rhg-combo-input', function () {
			const $combo = $(this).closest('.rhg-combo');
			renderComboMenu($combo, $(this).val());
		});

		// Toggle menu on arrow icon click
		$(document).on('click', '.rhg-combo-arrow', function () {
			const $combo = $(this).closest('.rhg-combo');
			const $menu  = $combo.find('.rhg-combo-menu');
			if ($menu.hasClass('open')) {
				closeComboMenu($combo);
			} else {
				renderComboMenu($combo, $combo.find('.rhg-combo-input').val());
				$combo.find('.rhg-combo-input').focus();
			}
		});

		// Close menu when clicking elsewhere
		$(document).on('click', function (e) {
			if (!$(e.target).closest('.rhg-combo').length) {
				closeAllComboMenus();
			}
		});

		// Country selection (via click or exact typed match) refreshes Town options
		$(document).on('rhg:comboSelect input', '#rhg-country-input', function () {
			// Clear previously chosen town since it may not belong to the new country
			$('#rhg-town-input').val('');
		});
	}

	/* ============================================================
	   REGISTRATION MODAL
	   ============================================================ */

	function openModal(exhibitionId, shortTitle, date, place) {
		$('#rhg-exh-exhibition-id').val(exhibitionId);
		$('#rhg-exh-modal-title').text(shortTitle);
		$('#rhg-modal-date-val').text(date || '—');
		$('#rhg-modal-place-val').text(place || '—');

		$('#rhg-exh-form-message').removeClass('success error').hide().text('');
		$('#rhg-exh-register-form')[0].reset();
		$('#rhg-exh-exhibition-id').val(exhibitionId);
		closeAllComboMenus();

		// Normal flow (opened from a card) — hide and un-require the
		// manual "requested exhibition" field.
		$('#rhg-manual-expo-field').hide();
		$('#rhg-requested-exhibition').prop('required', false);

		$('#rhg-exh-modal').fadeIn(150);
		$('body').css('overflow', 'hidden');
	}

	// "Can't find your exhibition?" flow — same modal, but there's no
	// specific listing to attach to, so the exhibition_id field stays
	// empty and the visitor types the name/date themselves instead.
	function openManualModal() {
		$('#rhg-exh-exhibition-id').val('');
		$('#rhg-exh-modal-title').text('Register Your Interest');
		$('#rhg-modal-date-val').text('Tell us which exhibition you\'re looking for');
		$('#rhg-modal-place-val').closest('.rhg-modal-meta-row').hide();

		$('#rhg-exh-form-message').removeClass('success error').hide().text('');
		$('#rhg-exh-register-form')[0].reset();
		closeAllComboMenus();

		$('#rhg-manual-expo-field').show();
		$('#rhg-requested-exhibition').prop('required', true);

		$('#rhg-exh-modal').fadeIn(150);
		$('body').css('overflow', 'hidden');
	}

	function closeModal() {
		$('#rhg-exh-modal').fadeOut(150);
		$('body').css('overflow', '');
		// Restore the date row's visibility for the next time a card
		// opens the modal normally (openManualModal hides it above).
		$('#rhg-modal-place-val').closest('.rhg-modal-meta-row').show();
	}

	function showSuccessPopup(message) {
		$('#rhg-success-message-text').text(message || 'Your registration has been received.');
		closeModal();
		$('#rhg-exh-success-modal').fadeIn(200);
		$('body').css('overflow', 'hidden');
	}

	function closeSuccessPopup() {
		$('#rhg-exh-success-modal').fadeOut(150);
		$('body').css('overflow', '');
	}

	$(document).ready(function () {
		loadExhibitions();
		initCombos();

		// Search with debounce
		$('#rhg-exh-search').on('input', function () {
			clearTimeout(searchTimer);
			const val = $(this).val();
			searchTimer = setTimeout(function () {
				currentSearch = val;
				currentPage   = 1;
				loadExhibitions();
			}, 400);
		});

		// Per-page select
		$('#rhg-exh-per-page').on('change', function () {
			currentPerPage = parseInt($(this).val(), 10);
			currentPage    = 1;
			loadExhibitions();
		});

		// Category pill click — multi-select toggle (same mechanism on
		// every screen size; CSS decides whether pills show as an inline
		// row or a mobile dropdown list).
		$(document).on('click', '.rhg-cat-pill', function () {
			toggleCategory( $(this).data('category') );
		});

		// Clear all selected categories
		$(document).on('click', '#rhg-clear-filter', function () {
			toggleCategory('');
		});

		// Mobile-only: tap the "Categories" button to open/close the
		// dropdown list. Has no effect on desktop (button is hidden there).
		$(document).on('click', '#rhg-cat-toggle', function (e) {
			e.stopPropagation();
			$('#rhg-exh-categories').toggleClass('rhg-cat-mobile-open');
			$(this).toggleClass('rhg-cat-toggle-open');
		});

		// Close the mobile category dropdown when tapping outside it.
		$(document).on('click', function (e) {
			if ( ! $(e.target).closest('#rhg-exh-categories, #rhg-cat-toggle').length ) {
				$('#rhg-exh-categories').removeClass('rhg-cat-mobile-open');
				$('#rhg-cat-toggle').removeClass('rhg-cat-toggle-open');
			}
			if ( ! hasHoverSupport && ! $(e.target).closest('.rhg-exh-card').length ) {
				$('.rhg-exh-card').removeClass('rhg-touch-open');
			}
		});

		// Open registration modal (delegated — cards are re-rendered via AJAX)
		$(document).on('click', '.rhg-exh-register-btn', function (e) {
			e.stopPropagation(); // don't let this also trigger the card's tap-to-open toggle below
			const $card = $(this).closest('.rhg-exh-card');
			openModal(
				$card.data('id'),
				$card.data('short'),
				$card.data('date'),
				$card.data('place')
			);
		});

		// "Can't find your exhibition?" banner — opens the same modal
		// in manual entry mode.
		$(document).on('click', '#rhg-not-found-btn', function () {
			openManualModal();
		});

		// Touch devices (phones/tablets) have no real ":hover", so the
		// hover-to-reveal description overlay would never be reachable
		// there. On such devices, tapping a card toggles it open instead
		// — closing any other card that was open (accordion-style).
		if ( ! hasHoverSupport ) {
			$(document).on('click', '.rhg-exh-card', function (e) {
				if ( $(e.target).closest('.rhg-exh-register-btn').length ) return;
				const $this   = $(this);
				const wasOpen = $this.hasClass('rhg-touch-open');
				$('.rhg-exh-card').removeClass('rhg-touch-open');
				if ( ! wasOpen ) {
					$this.addClass('rhg-touch-open');
				}
			});
		}

		// Close registration modal
		$(document).on('click', '.rhg-exh-modal-close, .rhg-exh-modal-overlay', function (e) {
			// Don't close the success popup's overlay by accident when both exist
			if ($(this).closest('#rhg-exh-success-modal').length) return;
			closeModal();
		});

		// Close success popup
		$(document).on('click', '#rhg-success-close', closeSuccessPopup);
		$('#rhg-exh-success-modal .rhg-exh-modal-overlay').on('click', closeSuccessPopup);

		$(document).on('keyup', function (e) {
			if ( e.key === 'Escape' ) {
				closeAllComboMenus();
				closeModal();
				closeSuccessPopup();
			}
		});

		// Submit registration form
		$(document).on('submit', '#rhg-exh-register-form', function (e) {
			e.preventDefault();

			const $form      = $(this);
			const $submitBtn = $form.find('.rhg-exh-submit-btn');
			const $message   = $('#rhg-exh-form-message');

			$submitBtn.prop('disabled', true).text('Submitting...');
			$message.removeClass('success error').hide().text('');

			$.post( rhgExhData.ajaxUrl, {
				action:                'rhg_exh_register',
				nonce:                 rhgExhData.nonce,
				exhibition_id:         $('#rhg-exh-exhibition-id').val(),
				requested_exhibition:  $form.find('[name="requested_exhibition"]').val(),
				full_name:             $form.find('[name="full_name"]').val(),
				email:                 $form.find('[name="email"]').val(),
				phone:                 $form.find('[name="phone"]').val(),
				job_title:             $form.find('[name="job_title"]').val(),
				country:               $form.find('[name="country"]').val(),
				town:                  $form.find('[name="town"]').val()
			}).done(function (response) {
				if ( response.success ) {
					$form[0].reset();
					showSuccessPopup(response.data.message);
				} else {
					$message.addClass('error').text(response.data.message).show();
				}
			}).fail(function () {
				$message.addClass('error').text('Something went wrong. Please try again.').show();
			}).always(function () {
				$submitBtn.prop('disabled', false).text('Submit Registration');
			});
		});
	});

})(jQuery);
