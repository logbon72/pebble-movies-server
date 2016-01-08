$.fn.mdlselect = function (options) {
  //  Function to provide a "material design" for the HTML <select> tag.
  //  Requires Google's mdl: http://www.getmdl.io/
  //  Places selected value in hidden field;

  if (this.length < 1) {
    return;
  }

  var defaults = {
    value: [],
    label: [],
    fixedHeight: false,
    firstValueDefault: true,
    hoverColor: "#a8a8a8"
  };
  var options = $.extend(defaults, options),
    field = this,
    elem = this.get(0),
    labelField = field.clone(),
    theHTML = "",
    selectUlId = 'mdl-mdlselect-list-' + elem.id,
    selectUlSelector = '#' + selectUlId,
    selectedValue = field.val()
    ;


  field.hide();
  labelField.attr('id', labelField.attr('id') + Date.now() + Math.random())
    .attr('readonly', true);
  labelField.removeAttr('name');
  field.before(labelField);

  var style = document.createElement('style');
  style.type = 'text/css';
  style.innerHTML = '.mdl-select-hoverable-action {cursor: pointer; background: ' + options.hoverColor + '}' +
    '.mdl-select-hide {display: none} .mdl-menu__container {right: 0 !important;}';
  document.getElementsByTagName('head')[0].appendChild(style);

  var hasOptions = (options.options && Object.keys(options.options).length > 0) || (options.value.length == options.label.length && options.value.length > 0);
  if (hasOptions && !options.options) {//map value/labels to options.
    options.options = {};

    options.value.forEach(function (v, i) {
      options.options[v] = options.label[i];
    });
  }


  if (hasOptions) {
    theHTML =
      '<div id="mdl-select-container-' + elem.id + '">' +
      '<button type="button" id="mdl_select_options_' + elem.id + '" class="mdl-button mdl-js-button mdl-button--icon" style="margin-left: -24px;">' +
      '<i id="show_mdl_select_options_icon_' + elem.id + '" class="material-icons">arrow_drop_down</i>' +
      '</button>' +
      '<ul id="' + selectUlId + '" style="right: 0;" class="mdl-menu mdl-menu--bottom-right mdl-js-menu mdl-js-ripple-effect mdl-select" for="mdl_select_options_' + elem.id + '">';

    for (var value in options.options) {
      theHTML +=
        '<li data-value="' + value + '" class="mdl-mdlselect-hoverable"><span style="padding:1em; display: block;">' + options.options[value] + '</span></li>';
      if (field.val() === value) {
        selectedValue = value;
      }
    }

    theHTML += '</ul></div>';
    //
    //                    Insert the content into DOM
    //
    var content = $(theHTML);
    content.css({"float": "right", "margin-top": ".75em;"});
    $(field.parent()).append(content);

    var menuCss = {"max-height": ($(window).innerHeight() * 0.6) + "px", "overflow-y": "scroll"};
    if (options.fixedHeight) {
      menuCss.height = options.fixedHeight;
    }
    $(selectUlSelector).css(menuCss);

    //var padding = parseInt(($(selectUlSelector).css('padding') || '').replace('px', '')) || 1;
    var width = labelField.width();// + $('#mdl_select_options_' + elem.id).width();
    $(selectUlSelector).width(width);

  }

  //
  //                    Listeners
  //

  var openListOptions = function () {
    var obj = $("#" + labelField.attr('id') + ":focus");
    if (obj) {
      obj.focus();
    }
  };

  $("body").click(function (target) { //hides the options when a click happens outside of the select area
    if ($(selectUlSelector).hasClass("mdl-select-hide") && target.target.id != elem.id && $.inArray(target.target, autoComplete.find('.mdl-select')) < 0) {
      $(selectUlSelector).removeClass("is-visible");
    }
  });

  $(".mdl-mdlselect-hoverable").hover(
    function (e) {
      $(e.currentTarget).addClass("mdl-select-hoverable-action");
    },
    function (e) {
      $(e.currentTarget).removeClass("mdl-select-hoverable-action");
    }
  );

  if (selectedValue) {
    setFieldValueLabel(selectedValue, options.options[selectedValue])
  } else if (options.firstValueDefault) {
    $(selectUlSelector).find('li:first-child').click();
  }

  labelField.click(function () {
    $('#mdl_select_options_' + elem.id).click();
  });

  //
  //                    Functions, functions, what's your conjunction?
  //

  $('#mdl_select_options_' + elem.id).click(function () {
    openListOptions();
  });

  function setFieldValueLabel(value, label, trigger) {
    $(field).val(value);
    labelField.val(label)
    $(field).parent("div").addClass("is-dirty");
    $(selectUlSelector).parent().removeClass("is-visible");
    if (trigger) {
      field.trigger('change');
    }
  }

  $(selectUlSelector).find('li').each(function (i, el) {
    $(el).click(function () {
      setFieldValueLabel($(el).data('value'), $(el).find('span').text(), true);
    });
  });


}