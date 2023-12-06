var btn_click_lable = ''

// Split order start
function loadSplitOrderItems (set_item_name, order_name) {
  if (localStorage.getItem(set_item_name)) {
    var customer = (localStorage.getItem('poscustomer')) ? localStorage.getItem('poscustomer') : ''

    total = 0
    count = 1
    an = 1
    product_tax = 0
    invoice_tax = 0
    product_discount = 0
    order_discount = 0
    total_discount = 0

    var positems = JSON.parse(localStorage.getItem(set_item_name))
    if (pos_settings.item_order == 1) {
      sortedItems = _.sortBy(positems, function (o) {
        return [parseInt(o.category), parseInt(o.order)]
      })
    } else if (site.settings.item_addition == 1) {
      sortedItems = _.sortBy(positems, function (o) {
        return [parseInt(o.order)]
      })
    } else {
      sortedItems = positems
    }
    var category = 0, print_cate = false


    var post_html_hidden_elements = ''
    post_html_hidden_elements += "<input type='hidden' name='customer' value='" + customer + "'>"
    post_html_hidden_elements += "<input type='hidden' name='warehouse' value='" + $('#poswarehouse').val() + "' >"
    post_html_hidden_elements += "<input type='hidden' name='biller' value='" + $('#posbiller').val() + "' >"
    post_html_hidden_elements += "<input type='hidden' name='suspend' value='yes' >"
    post_html_hidden_elements += "<input type='hidden' name='suspend_note' value='" + order_name + "' >"
    post_html_hidden_elements += "<input type='hidden' name='staff_note' value='' >"

    $.each(sortedItems, function () {
      var item = this
      var item_id = site.settings.item_addition == 1 ? item.item_id : item.id
      if (item.options) {
        item_id = item_id + '' + item.row.option
      }
      // console.log(item_id);
      var hsn_code = item.row.hsn_code
      positems[item_id] = item
      item.order = item.order ? item.order : new Date().getTime()
      var product_id = item.row.id, item_type = item.row.type, combo_items = item.combo_items,
        item_price = item.row.price, item_qty = item.row.qty, item_aqty = item.row.quantity,
        item_tax_method = item.row.tax_method, item_ds = item.row.discount, item_discount = 0,
        item_option = item.row.option, item_code = item.row.code, item_serial = item.row.serial,
        item_name = item.row.name.replace(/"/g, '&#034;').replace(/'/g, '&#039;')
      var product_unit = item.row.unit, base_quantity = item.row.base_quantity
      var unit_price = item.row.real_unit_price

      var cf1 = item.row.cf1
      var cf2 = item.row.cf2
      var cf3 = item.row.cf3
      var cf4 = item.row.cf4
      var cf5 = item.row.cf5
      var cf6 = item.row.cf6

      if (item.row.fup != 1 && product_unit != item.row.base_unit) {
        $.each(item.units, function () {
          if (this.id == product_unit) {
            base_quantity = formatDecimal(unitToBaseQty(item.row.qty, this), 4)
            unit_price = formatDecimal((parseFloat(item.row.base_unit_price) * (unitToBaseQty(1, this))), 4)
          }
        })
      }
      if (item.options !== false) {
        $.each(item.options, function () {
          if (this.id == item.row.option && this.price != 0 && this.price != '' && this.price != null) {
            item_price = parseFloat(unit_price) + (parseFloat(this.price))
            unit_price = item_price
          }
        })
      }

      var ds = item_ds || '0'
      if (ds.indexOf('%') !== -1) {
        var pds = ds.split('%')
        if (!isNaN(pds[0])) {
          item_discount = formatDecimal((parseFloat(((unit_price) * parseFloat(pds[0])) / 100)), 4)
        } else {
          item_discount = formatDecimal(ds)
        }
      } else {
        item_discount = formatDecimal(ds)
      }
      product_discount += formatDecimal(item_discount * item_qty)

      unit_price = formatDecimal(unit_price - item_discount)
      var pr_tax = item.tax_rate
      var pr_tax_val = 0, pr_tax_rate = 0
      if (site.settings.tax1 == 1) {
        if (pr_tax !== false) {
          if (pr_tax.type == 1) {
            if (item_tax_method == '0') {
              pr_tax_val = formatDecimal(((unit_price) * parseFloat(pr_tax.rate)) / (100 + parseFloat(pr_tax.rate)), 4)
              pr_tax_rate = formatDecimal(pr_tax.rate) + '%'
            } else {
              pr_tax_val = formatDecimal(((unit_price) * parseFloat(pr_tax.rate)) / 100, 4)
              pr_tax_rate = formatDecimal(pr_tax.rate) + '%'
            }
          } else if (pr_tax.type == 2) {
            pr_tax_val = formatDecimal(pr_tax.rate)
            pr_tax_rate = pr_tax.rate
          }
          product_tax += pr_tax_val * item_qty
        }
      }
      item_price = item_tax_method == 0 ? formatDecimal((unit_price - pr_tax_val), 4) : formatDecimal(unit_price)
      unit_price = formatDecimal((unit_price + item_discount), 4)
      var sel_opt = ''
      $.each(item.options, function () {
        if (this.id == item_option) {
          sel_opt = this.name
        }
      })

      if (pos_settings.item_order == 1 && category != item.row.category_id) {
        category = item.row.category_id
        print_cate = true
      } else {
        print_cate = false
      }

      total += formatDecimal(((parseFloat(item_price) + parseFloat(pr_tax_val)) * parseFloat(item_qty)), 4)
      count += parseFloat(item_qty)
      var row_no = (new Date()).getTime()

      // post item wise values
      post_html_hidden_elements += "<input type='hidden' name='row[]' value='" + row_no + "' >"
      post_html_hidden_elements += "<input type='hidden' name='product_id[]' value='" + item.row.id + "' >"
      post_html_hidden_elements += "<input type='hidden' name='hsn_code[]' value='" + item.row.hsn_code + "' >"
      post_html_hidden_elements += "<input type='hidden' name='product_type[]' value='" + item.row.type + "' >"
      post_html_hidden_elements += "<input type='hidden' name='product_code[]' value='" + item.row.code + "' >"
      post_html_hidden_elements += "<input type='hidden' name='product_name[]' value='" + item.row.name.replace(/"/g, '&#034;').replace(/'/g, '&#039;') + "' >"
      post_html_hidden_elements += "<input type='hidden' name='product_option[]' value='" + item.row.option + "'>" // true/false
      post_html_hidden_elements += "<input type='hidden' name='cf1[]' value='" + item.row.cf1 + "' >"
      post_html_hidden_elements += "<input type='hidden' name='cf2[]' value='" + item.row.cf2 + "' >"
      post_html_hidden_elements += "<input type='hidden' name='cf3[]' value='" + item.row.cf3 + "' >"
      post_html_hidden_elements += "<input type='hidden' name='cf4[]' value='" + item.row.cf4 + "' >"
      post_html_hidden_elements += "<input type='hidden' name='cf5[]' value='" + item.row.cf5 + "' >"
      post_html_hidden_elements += "<input type='hidden' name='cf6[]' value='" + item.row.cf6 + "' >"
      post_html_hidden_elements += "<input type='hidden' name='serial[]' value='" + item.row.cf1 + "' >"
      post_html_hidden_elements += "<input type='hidden' name='product_discount[]' value='" + item.row.discount + "' >"
      post_html_hidden_elements += "<input type='hidden' name='product_tax[]' value='" + pr_tax.id + "' >"
      post_html_hidden_elements += "<input type='hidden' name='net_price[]' value='" + item_price + "' >"
      post_html_hidden_elements += "<input type='hidden' name='unit_price[]' value='" + unit_price + "' >"
      post_html_hidden_elements += "<input type='hidden' name='real_unit_price[]' value='" + item.row.real_unit_price + "' >"
      post_html_hidden_elements += "<input type='hidden' name='quantity[]' value='" + formatDecimal(item_qty) + "' >"
      post_html_hidden_elements += "<input type='hidden' name='product_unit[]' value='" + product_unit + "' >"
      post_html_hidden_elements += "<input type='hidden' name='product_base_quantity[]' value='" + base_quantity + "' >"
      post_html_hidden_elements += "<input type='hidden' name='amount[]' value='' >"
      post_html_hidden_elements += "<input type='hidden' name='balance_amount[]' value='' >"
      post_html_hidden_elements += "<input type='hidden' name='paid_by[]' value='' >"
      post_html_hidden_elements += "<input type='hidden' name='cc_no[]' 
                                                         