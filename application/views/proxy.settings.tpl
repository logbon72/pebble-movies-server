{strip}
    <!DOCTYPE html>
    <html>
        <head>
            <title>Pebble Movies</title>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <link rel="stylesheet" href="http://code.jquery.com/mobile/1.3.2/jquery.mobile-1.3.2.min.css" />
            <script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
            <script src="http://code.jquery.com/mobile/1.3.2/jquery.mobile-1.3.2.min.js"></script>
        </head>
        <body>
            <div data-role="page" id="main">
                <div data-role="header" class="jqm-header">
                    <h1>Settings</h1>
                </div>

                <div data-role="content">
                    {if $hasUpdate}
                        <div style="text-align: center; color: #28a4c9; padding: 5px; border: solid 1px #269abc; border-radius: 3px; margin: 5px 0;">
                            A newer version of this app is available. Visit Pebble App store to update
                        </div>
                    {/if}
                    {*var SETTING_DEFAULT_POSTAL_CODE = "PostalCode";
                    var SETTING_DEFAULT_CITY = "DefaultCity";
                    var SETTING_DEFAULT_COUNTRY = "DefaultCountry";
                    var SETTING_DEFAULT_UNIT = "DefaultUnit";*}

                    {*$countryIso = $this->_request->getQueryParam('country');
                    $city = $this->_request->getQueryParam('city');
                    $postalCode = $this->_request->getQueryParam('postalCode');*}
                    <div>
                        These opions will be used if your current location is not available.
                    </div>    
                    <div data-role="fieldcontain">
                        <label for="DefaultCountry">Country:</label>
                        <select name="DefaultCountry" id="DefaultCountry" class="setting">
                            <option></option>
                            {if $smarty.get.country}
                                {$selectedCountry =  $smarty.get.country}
                            {elseif $geocode}
                                {$selectedCountry =  $geocode->country_iso}
                            {/if}
                            {html_options options=$availableCountries selected=$selectedCountry}
                        </select>
                    </div>
                    <div data-role="fieldcontain">
                        <label for="PostalCode">Postal Code</label>
                        {if $smarty.get.postalCode}
                            {$postalCode =  $smarty.get.postalCode}
                        {elseif $geocode}
                            {$postalCode =  $geocode.postal_code}
                        {/if}
                        <input name="PostalCode" id="PostalCode" class="setting" value="{$postalCode}">
                    </div>
                    <div data-role="fieldcontain">
                        <label for="DefaultCity">City</label>
                        {if $smarty.get.city}
                            {$city =  $smarty.get.city}
                        {elseif $geocode}
                            {$city =  $geocode.city}
                        {/if}
                        <input name="DefaultCity" id="DefaultCity" class="setting" value="{$city}">
                    </div>

                    <div data-role="fieldcontain">
                        <label for="DefaultUnit">Distance Unit</label>
                        <select name="DefaultUnit" id="DefaultUnit" data-role="slider" style="width: 7em;" class="setting">
                            {$units = ["km" => "Km", "mi"=>"Miles"]}
                            {html_options options=$units selected=$smarty.request.unit}
                        </select>
                    </div>
                </div>

                <div class="ui-body ui-body-b">
                    <fieldset class="ui-grid-a">
                        <div class="ui-block-a"><button type="submit" data-theme="d" id="b-cancel">Cancel</button></div>
                        <div class="ui-block-b"><button type="submit" data-theme="a" id="b-submit">Submit</button></div>
                    </fieldset>
                </div>
            </div>
        </div>
    </div>
    {literal}
        <script>
            function saveOptions() {
                var options = {}
                $('.setting').each(function(i, el) {
                    if (el.id && el.id.length) {
                        options[el.id] = $(el).val();
                    }
                });
                return options;
            }

            $().ready(function() {
                $("#b-cancel").click(function() {
                    console.log("Cancel");
                    document.location = "pebblejs://close";
                });

                $("#b-submit").click(function() {
                    console.log("Submit");

                    var location = "pebblejs://close#" + encodeURIComponent(JSON.stringify(saveOptions()));
                    console.log("Warping to: " + location);
                    //console.log(location);
                    document.location = location;
                });

            });
        </script>
    {/literal}
</body>
</html>
{/strip}