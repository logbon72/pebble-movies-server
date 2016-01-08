{strip}
    <!DOCTYPE html>
    <html>
    <head>
        <title>Pebble Movies</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="https://storage.googleapis.com/code.getmdl.io/1.0.6/material.red-pink.min.css"/>
        <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
        <script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
        <script src="https://storage.googleapis.com/code.getmdl.io/1.0.6/material.min.js"></script>
        <script src="{assets_url}js/mdl-select.js"></script>
        <style>

        </style>
    </head>

    <body>


    <!-- Always shows a header, even in smaller screens. -->
    <div class="mdl-layout mdl-js-layout mdl-color--white-400">

        <div class="mdl-card__title mdl-color--primary">
            <h2 class="mdl-card__title-text mdl-color-text--white" style="display: block">Settings</h2>
        </div>


        <main class="mdl-layout__content">
            <div class="page-content">

                {if true || $hasUpdate}
                    <div style="text-align: center; color: #fff; padding: 10px" class="mdl-color--blue-500">
                        A newer version of this app is available. Visit Pebble App store to update
                    </div>
                {/if}


                <form method="POST" action="#" onsubmit="return false;" id="settingsForm">

                    <div class="mdl-grid">
                        {if $smarty.get.country}
                            {$selectedCountry =  $smarty.get.country}
                        {elseif $geocode}
                            {$selectedCountry =  $geocode->country_iso}
                        {/if}

                        <div class="mdl-cell mdl-cell--12-col mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input name="DefaultCountry" id="DefaultCountry"
                                   class="setting mdl-textfield__input setting" value="{$selectedCountry}"
                                   data-options='{$availableCountries|@json_encode}'>
                            <label class="mdl-textfield__label" for="DefaultCountry">Country</label>
                        </div>
                    </div>

                    {if $smarty.get.postalCode}
                        {$postalCode =  $smarty.get.postalCode}
                    {elseif $geocode}
                        {$postalCode =  $geocode.postal_code}
                    {/if}

                    <div class="mdl-grid">
                        <div class="mdl-cell mdl-cell--12-col mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input name="PostalCode" id="PostalCode" class="mdl-textfield__input setting"
                                   value="{$postalCode}">
                            <label class="mdl-textfield__label" for="PostalCode">Postal Code</label>
                        </div>
                    </div>


                    {if $smarty.get.city}
                        {$city =  $smarty.get.city}
                    {elseif $geocode}
                        {$city =  $geocode.city}
                    {/if}
                    <div class="mdl-grid">
                        <div class="mdl-cell mdl-cell--12-col mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input name="DefaultCity" id="DefaultCity" class="setting mdl-textfield__input setting"
                                   value="{$city}">
                            <label class="mdl-textfield__label" for="DefaultCity">City</label>
                        </div>
                    </div>


                    {$units = ["km" => "Km", "mi"=>"Miles"]}
                    <div class="mdl-grid">
                        <div class="mdl-cell mdl-cell--12-col mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input class="mdl-textfield__input" type="text" id="DefaultUnit" name="DefaultUnit"
                                   data-options='{$units|@json_encode}' value="{$smarty.request.unit}"/>
                            <label class="mdl-textfield__label" for="select">Distance Unit</label>
                        </div>
                    </div>

                    {if $showForceLocation}
                        <div class="mdl-grid">
                            <div class="mdl-cell mdl-cell--3-col-phone  mdl-cell--7-col-tablet mdl-cell--11-col-desktop">
                                <label for="ForceLocation">
                                    Force this Location?
                                    <br/>
                                    <small>
                                        This option is useful if you don't move around much. It is however recommended
                                        that you leave this as No.
                                    </small>
                                </label>
                            </div>

                            <div class="mdl-cell mdl-cell--1-col-tablet mdl-cell--1-col-phone mdl-cell--1-col-desktop"
                                 style="text-align: right;">
                                <input type="hidden" name="ForceLocation" value="0" id="ForceLocation-hidden"/>
                                <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect">
                                    <input type="checkbox" id="ForceLocation" name="ForceLocation"
                                           class="mdl-switch__input" value="1"
                                           {if $smarty.request.forceLocation}checked{/if}>
                                </label>
                            </div>
                        </div>
                    {/if}

                    <div class="mdl-grid">
                        <button id="b-submit"
                                class="mdl-cell mdl-cell--6-col-desktop mdl-cell--2-col--tablet mdl-cell--2-col--phone mdl-button mdl-js-button mdl-js-ripple-effect">
                            Save
                        </button>

                        <button id="b-cancel"
                                class="mdl-cell mdl-cell--6-col-desktop mdl-cell--2-col--tablet mdl-cell--2-col--phone mdl-button mdl-js-button mdl-js-ripple-effect">
                            Cancel
                        </button>
                    </div>

                </form>

            </div>
        </main>
    </div>

    {literal}
        <script>
            (function () {

                $('input[data-options]').each(function (i, el) {
                    var opt = $(el).data();
                    $(el).mdlselect(opt);
                });


                function getQueryParam(variable, defaultValue) {
                    // Find all URL parameters
                    var query = location.search.substring(1);
                    var vars = query.split('&');
                    for (var i = 0; i < vars.length; i++) {
                        var pair = vars[i].split('=');

                        // If the query variable parameter is found, decode it to use and return it for use
                        if (pair[0] === variable) {
                            return decodeURIComponent(pair[1]);
                        }
                    }
                    return defaultValue || false;
                }


                function saveOptions() {
                    var options = {};
                    $('#settingsForm').serializeArray().forEach(function (field) {
                        options[field.name] = field.value;
                    });

                    console.log('Options:', options);
                    return options;
                }

                $().ready(function () {
                    var returnUrl = getQueryParam('return_to', 'pebblejs://close#');
                    $("#b-cancel").click(function (e) {
                        e.preventDefault();
                        console.log("Cancelled");
                        document.location = returnUrl;
                    });

                    $("#b-submit").click(function (e) {
                        e.preventDefault();
                        var location = returnUrl + encodeURIComponent(JSON.stringify(saveOptions()));
                        console.log("Warping to: " + location);
                        document.location = location;
                    });

                });
            })();
        </script>
    {/literal}
    </body>
    </html>
{/strip}