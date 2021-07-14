(function ($) {
	'use strict';
	$(function () {
		// for export method.
		$( '#esraw_shipping_export_field' ).select2({ minimumResultsForSearch: 2 });
		$( '#esraw_shipping_zones_import' ).select2({ minimumResultsForSearch: 2 });

		$("select[id*='easy_rate_condition_']").each(function (index) {
			$(this).on('change', function () {
				let condition_val = $(this).val();
				let add_for_calcul = create_operators(index, condition_val);
				$('#easy_rate_operator_content_' + index).html(add_for_calcul);
				$( '#esraw_ship_class' ).select2({ minimumResultsForSearch: 2 });
				$( '#esraw_user_roles' ).select2({ minimumResultsForSearch: 2 });
			});
		});

		$(".esr_remove_all_tr").on('change', function () {
			if (this.checked) {
				$(".esr_remove_tr").each(function () {
					$(this).prop('checked', true);
				});
			} else {
				$(".esr_remove_tr").each(function () {
					$(this).prop('checked', false);
				});
			}
		});

		$("#esr-remove-rows").on('click', function (e) {
			e.preventDefault();
			$(".esr_remove_tr").each(function () {
				if (this.checked) {
					$(this).closest('tr').remove();
					$(".esr_remove_all_tr").prop('checked', false);
				}
			});
		});

		$("#esr-insert-new-row").on('click', function (e) {
			e.preventDefault();

			let tr_last = $("#esr-woo-table tbody tr:last");
			let last_tr_key = tr_last.data('key');
			if (isNaN(last_tr_key)) {
				last_tr_key = 0;
			} else {
				last_tr_key = last_tr_key + 1;
			}

			let form_add = create_content(last_tr_key);
			$("#esr-woo-table tbody").append(form_add);
			$('#easy_rate_condition_' + last_tr_key).on('change', function (e) {
				let condition_val = $(this).val();
				let add_for_calcul = create_operators(last_tr_key, condition_val);
				$('#easy_rate_operator_content_' + last_tr_key).html(add_for_calcul);
				$( '#esraw_ship_class' ).select2({ minimumResultsForSearch: 2 });
				$( '#esraw_user_roles' ).select2({ minimumResultsForSearch: 2 });
			});
		});
		//select2
		$( '#esraw_ship_class' ).select2({ minimumResultsForSearch: 2 });
		$( '#esraw_user_roles' ).select2({ minimumResultsForSearch: 2 });

		function create_content(p) {
			let cond_choices = esr_vars.esraw_condition_choices;
			let cond_actions = esr_vars.esraw_condition_actions;

			let cond_choices_options = generate_select_option_for_group(cond_choices); // get condition options.
			let cond_actions_options = generate_select_option(cond_actions); // get action options.

			let content = '<tr data-key="' + p + '"><td><input type="checkbox" class="esr_remove_tr"></td>' +
				'<td><div class="easy_rate_condition_content" id="easy_rate_condition_content_' + p + '"><select id="easy_rate_condition_' + p +
				'" name="easy_rate[' + p + '][condition]" required>' + cond_choices_options + '</select>' +
				'<span class="easy_rate_operator_content" id="easy_rate_operator_content_' + p + '"></span></div></td>' +
				'<td><input type="number"  step="0.01" name="easy_rate[' + p + '][cost]" required/></td>' +
				'<td><select id="easy_rate_action_' + p + '" name="easy_rate[' + p + '][action]">' + cond_actions_options + '</select></td></tr>';
			return content;
		}

		function generate_select_option_for_group(select_choices) {
			let select_options = '<option></option>';

			for (const key in select_choices) {
				if (Object.hasOwnProperty.call(select_choices, key)) {
					const choices = select_choices[key];
					select_options += '<optgroup label="' + key + '">';
					for (const choices_key in choices) {
						if (Object.hasOwnProperty.call(choices, choices_key)) {
							const choice = choices[choices_key];
							select_options += '<option value="' + choice + '">' + choices_key + '</option>';

						}
					}
					select_options += '</optgroup>';
				}
			}
			return select_options;
		}

		function generate_select_option(select_choices) {
			let select_options = '';
			for (const key in select_choices) {
				if (Object.hasOwnProperty.call(select_choices, key)) {
					const choices = select_choices[key];
					select_options += '<option value="' + key + '">' + choices + '</option>';
				}
			}
			return select_options;
		}

		function create_operators(p, val) {
			let content = '';
			let unit = '';

			if (val == 'subtotal' || 'subtotal_ex' == val) {
				unit = esr_vars.esraw_currency_symbol;
			} else if ('quantity' == val || 'cart_line_item' == val) {
				unit = 'qty';
			} else if ('weight' == val || 'dim_weight' == val) {
				unit = 'kg';
			} else if ('volume' == val) {
				unit = 'cm<sup>3</sup>';
			} else if ('max_dim' == val || 'total_dim' == val) {
				unit = 'cm';
			}


			let operators = esr_vars.esraw_operator;
			let select_options = '';

			for (const key in operators) {
				if (Object.hasOwnProperty.call(operators, key)) {
					const choices = operators[key];
					select_options += '<option value="' + key + '">' + choices + '</option>';
				}
			}

			if ('subtotal' == val || 'weight' == val || 'dim_weight' == val || 'volume' == val || 'max_dim' == val || 'total_dim' == val || 'subtotal_ex' == val || 'quantity' == val || 'cart_line_item' == val) {
				content = '<select id="easy_rate_operator_' + p + '" name="easy_rate[' + p + '][operator]" required>' + select_options + '</select>' +
					'<input type="number"  step="0.01" placeholder="from" name="easy_rate[' + p + '][operand1]"/>' +
					'<input type="number" step="0.01" placeholder="to" name="easy_rate[' + p + '][operand2]"/><div class="easy_rate_unit">' + unit + '</div>';
			} else if ('contains_shipping_class' == val) {

				let ship_classes = esr_vars.esraw_ship_classes_array;
				let ship_options = '';

				for (const key_ship in ship_classes) {
					if (Object.hasOwnProperty.call(ship_classes, key_ship)) {
						const choices = ship_classes[key_ship];
						ship_options += '<option value="' + key_ship + '">' + choices + '</option>';
					}
				}

				content = '<select id="easy_rate_operator_' + p + '" name="easy_rate[' + p + '][operator]" required>' + select_options + '</select>' +
					'<select multiple id="esraw_ship_class" style="overflow: scroll; height: 35px;" name="easy_rate[' + p + '][choices][]" required>' + ship_options + '</select>';
			} else if ('user_roles' == val) {
				let user_roles = esr_vars.esraw_user_roles;
				let options = '';

				for (const key_ship in user_roles) {
					if (Object.hasOwnProperty.call(user_roles, key_ship)) {
						const choices = user_roles[key_ship];
						options += '<option value="' + key_ship + '">' + choices + '</option>';
					}
				}

				content = '<select id="easy_rate_operator_' + p + '" name="easy_rate[' + p + '][operator]" required>' + select_options + '</select>' +
					'<select multiple id="esraw_user_roles" style="overflow: scroll; height: 35px;" name="easy_rate[' + p + '][choices][]" required>' + options + '</select>';
			} else if ('zipcode' == val) {
				content = '<select id="easy_rate_operator_' + p + '" name="easy_rate[' + p + '][operator]" required>' + select_options + '</select>' +
					'<input type="text" placeholder="postcode1,postcode2,etc." name="easy_rate[' + p + '][choices]"/>';
			} else if ('city' == val) {
				content = '<select id="easy_rate_operator_' + p + '" name="easy_rate[' + p + '][operator]" required>' + select_options + '</select>' +
					'<input type="text" placeholder="city1,city2,etc." name="easy_rate[' + p + '][choices]"/>';
			}

			return content;
		}
	});

})(jQuery);
