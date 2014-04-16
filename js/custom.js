availableCurrencies = [];
debug = true;/*set to true to see the magic appear in the web console(understand:be flooded log info)*/

loading = new Object();
loading.home = true;
loading.host = true;

$(function() {
  $(document).foundation();
  inputHomeCurrency = document.getElementById("inputHomeCurrency");
  inputHostCurrency = document.getElementById("inputHostCurrency");
  converterHomeCurrencyTip = document.getElementById("converterHomeCurrencyTip");
  converterHostCurrencyTip = document.getElementById("converterHostCurrencyTip");
  targetValueInput = document.getElementById("targetValueInput");
  targetValueEmailInput = document.getElementById("targetValueEmailInput");

  if(!checkCookie()){
    $("#joyRideTipContent").joyride({
      autoStart : true
    });
    window.homeCurrency = "";
    window.hostCurrency = "";
  }
  else{
    var value = getCookie('save');
    var currencies = value.split(',');
    window.homeCurrency = currencies[0];
    inputHomeCurrency.value = currencies[0];
    window.hostCurrency = currencies[1];
    inputHostCurrency.value = currencies[1];
  }

  var query = "https://www.googleapis.com/fusiontables/v1/query?sql=SELECT+'ISO+code'+++FROM+1bTMHLs315h5NmkkucBAXYSeQjoiGXQyw0-4y_1nP+GROUP+BY+'ISO+code'+ORDER+BY+'ISO+code'+ASC&hdrs=false&typed=true&key=AIzaSyCCdCZEl31z9YPzDfX7kNOYY09ErJwCArM"
  $.ajax({
    url: query,
    cache: false
  })
  .done(function( json ) {
    for (var i = 0; i< json.rows.length; i++) {
      availableCurrencies.push(json.rows[i][0]);
    };
    $( ".currencyselector" ).autocomplete({
      source: availableCurrencies,
      autoFocus: true,
      delay: 200
    });
    $('.ui-autocomplete').addClass('f-dropdown');
    log(availableCurrencies);
  });

  getPopularCurrencies();

  inputHomeCurrency.addEventListener("input", currencyHandler, true);
  inputHostCurrency.addEventListener("input", currencyHandler, true);
  document.getElementById("targetValueEmailButton").addEventListener("click", setThreshold, false);
  document.getElementById("targetValueResetButton").addEventListener("click", changeTargetValueInput, false);
  intervalCurrencies = setInterval(currencyHandler, 1000);
  currencyHandler();
  

});

function setThreshold(){
  log(targetValueInput.value+" at "+targetValueEmailInput.value+ " for " + window.homeCurrency + " and "+ window.hostCurrency);
  if(targetValueInput.value!=0 && targetValueInput.value!="" && targetValueEmailInput.value!=""){
    $.ajax({
      dataType: "jsonp",
      url: "http://lemichel.eu/rgu/kurrencykonverter/controller.php?action=setthreshold&home="+window.homeCurrency+"&host="+window.hostCurrency+"&ratio="+targetValueInput.value+"&email="+encodeURI(targetValueEmailInput.value)+"&ratioinit="+((window.homeRate/window.hostRate).toFixed(3))+"&callback=mycallback",
      success: function( response ) {
        log( response );
        if(response.status=='bademail'){
          alert("Invalid email address.");
        }
        if(response.status=='ok'){
          alert("Target value set.");
        }
      }
    });
  }
  else{
    alert("Check the target value and the email address");
  }
}

function currencyHandler(){
  if(checkInput(inputHomeCurrency)){
    if(window.homeCurrency != inputHomeCurrency.value || loading.home){
      loading.home = false;
      log(inputHomeCurrency);
      colorText(inputHomeCurrency,"green");
      converterHomeCurrencyTip.innerHTML = inputHomeCurrency.value;
      window.homeCurrency = inputHomeCurrency.value;
      getTrend();
      getBeer();
    }
  }else{
    colorText(inputHomeCurrency,"red");
  }
  if(checkInput(inputHostCurrency)){
    if(window.hostCurrency != inputHostCurrency.value || loading.host){
      loading.host = false;
      log(inputHostCurrency);
      colorText(inputHostCurrency,"green");
      converterHostCurrencyTip.innerHTML = inputHostCurrency.value;
      window.hostCurrency = inputHostCurrency.value;
    }
  }else{
    colorText(inputHostCurrency,"red");
  }
  if(checkInput(inputHomeCurrency) && checkInput(inputHostCurrency)){
    log("2 currencies selected, let's rock");

    getRates();
    getPopularCurrencies();
    drawVisualization();
    clearInterval(intervalCurrencies);
    intervalCurrencies = false;
    setCookie('save', window.homeCurrency+','+window.hostCurrency);
  }else{
    if(!intervalCurrencies)
      intervalCurrencies = setInterval(currencyHandler, 1000);

  }
}

function changeTargetValueInput(){
  targetValueInput.value = (window.homeRate/window.hostRate).toFixed(3);
}

function getBeer(){
  $.ajax({
    dataType: "jsonp",
    url: "http://lemichel.eu/rgu/kurrencykonverter/controller.php?action=getbeerprices&home="+window.homeCurrency+"&callback=mycallback",
    success: function( response ) {
      log( response );
      priceDiv = document.getElementsByClassName("priceDiv");
      $.each(response, function(key, val) {
        var value = parseFloat(val.price);
        var div = priceDiv[key];
        var countryFlag = val.country.replace(/ /g,"_");;
        div.getElementsByClassName("price")[0].innerHTML=value.toFixed(2);
        div.getElementsByClassName("flag")[0].alt=val.country;
        div.getElementsByClassName("flag")[0].src="img/flags/"+countryFlag+".png";
        div.title=val.country;

      });
    },
    complete: function(){
      var query = "https://www.googleapis.com/fusiontables/v1/query?sql=SELECT+Sign+FROM+1bTMHLs315h5NmkkucBAXYSeQjoiGXQyw0-4y_1nP+WHERE+'ISO+code'%3D'EUR'+LIMIT+1&hdrs=false&typed=true&key=AIzaSyCCdCZEl31z9YPzDfX7kNOYY09ErJwCArM"
      $.ajax({
        url: query,
        cache: false
      })
      .done(function( json ) {
        log(json.rows[0][0]);
        for(var i=0; i < priceDiv.length; i++){
          priceDiv[i].getElementsByClassName("price")[0].innerHTML = json.rows[0][0] + priceDiv[i].getElementsByClassName("price")[0].innerHTML;
        }
      });
    }
  });
}

function getTrend(){
  $.ajax({
    dataType: "jsonp",
    url: "http://lemichel.eu/rgu/kurrencykonverter/controller.php?action=gettrend&home="+window.homeCurrency+"&callback=mycallback",
    success: function( response ) {
      log( response );
      document.getElementById("trend").innerHTML = response.trend;
    }
  });
}

function getRates(){

  $.ajax({
    dataType: "jsonp",
    url: "http://lemichel.eu/rgu/kurrencykonverter/controller.php?action=getrates&home="+window.homeCurrency+"&host="+window.hostCurrency+"&callback=mycallback",
    success: function( response ) {
      saveRates( response );
    }
  });

  log("getRates() finished");
}

function saveRates( json ){
  log(json[0]);
  log(json[1]);
  if(json[1]!== undefined){
    if(json[0].currency_code==window.homeCurrency){
      window.homeRate = json[0].value;
      window.hostRate = json[1].value;
    }else{
      window.homeRate = json[1].value;
      window.hostRate = json[0].value;
    }
  }
  else
  {
    window.homeRate = json[0].value;
  }
  setConverter();
  changeTargetValueInput();

}

function setConverter(){
  converterHomeCurrency = document.getElementById("converterHomeCurrency");
  converterHomeCurrency.addEventListener("input", converter, true);
  converterHostCurrency = document.getElementById("converterHostCurrency");
  converterHostCurrency.addEventListener("input", converter, true);
  converterHomeCurrency.placeholder = (1/window.homeRate*window.hostRate).toFixed(3);
  converterHostCurrency.placeholder = (1/window.hostRate*window.homeRate).toFixed(3);
}

function drawVisualization() {
  $.ajax({
    dataType: "jsonp",
    url: "http://lemichel.eu/rgu/kurrencykonverter/controller.php?action=getcurrencieshistory&home="+window.homeCurrency+"&host="+window.hostCurrency+"&callback=mycallback",
    success: function( response ) {
      log(response);

      data1 = [];
      data2 = [];
      $.each(response, function(key, val) {
        value = parseFloat(val.value);
        date = parseDate(val.time);
        if(val.currency_code==window.homeCurrency){
          data1.push([date.getTime(), value]);
        }
        else{
          data2.push([date.getTime(), value]);
        }
      });



      $('#container').highcharts('StockChart', {
        rangeSelector : {
          selected : 1,
          inputEnabled: $('#container').width() > 500,
          buttons : [{
            type: 'day',
            count: 1,
            text: '1d'
          }, {
            type: 'day',
            count: 7,
            text: '7d'
          }, {
            type: 'month',
            count: 1,
            text: '1m'
          }, {
            type: 'month',
            count: 6,
            text: '6m'
          }, {
            type: 'year',
            count: 1,
            text: '1y'
          }, {
            type: 'all',
            text: 'All'
          }]
        },

        yAxis: {
          labels: {
            formatter: function() {
              return (this.value > 0 ? '+' : '') + this.value + '%';
            }
          }
        },
        plotOptions: {
          series: {
            compare: 'percent'
          }
        }, 

        title : {
          text : window.homeCurrency+' vs '+window.hostCurrency
        },

        series : [{
          name: window.homeCurrency,
          data: data1,
          pointInterval: 3600 * 1000,
        }, 
        {
          name: window.hostCurrency,
          data: data2,
          pointInterval: 3600 * 1000,
        }],

        chart: {

          borderRadius: 0,
          plotBackgroundColor: {
            linearGradient: {
              x1: 0, 
              y1: 0, 
              x2: 1, 
              y2: 1
            },
            stops: [
            [0, '#BDAEC6'],
            [1, '#9C8AA5']
            ]
          }
        }
      });
}
});
}/*end function drawVisualization*/

function getPopularCurrencies(){
  $.ajax({
    dataType: "jsonp",
    url: "http://lemichel.eu/rgu/kurrencykonverter/controller.php?action=getpopularcurrencies"+(window.homeCurrency==""?"":"&home="+window.homeCurrency)+(window.hostCurrency==""?"":"&host="+window.hostCurrency)+"&callback=mycallback",
    success: function( response ) {
      log( response );
      var symboldiv = document.getElementsByClassName("symbol-div");
      var percentdiv = document.getElementsByClassName("percent-div");
      var valuediv = document.getElementsByClassName("value-div");
      var arrowdiv = document.getElementsByClassName("arrow-div");
      $.each( response, function( key, value ) {
        symboldiv[key].textContent = value[0].currency_code
        valuediv[key].textContent = (1/(value[0].value)).toFixed(3);
        log(value[0].percent);
        var percent = new Number(value[0].percent);
        var percentFormatted = percent.toFixed(3);
        percentdiv[key].textContent = percentFormatted+"%";
        if(percent>=0){
          arrowdiv[key].className="arrow-div arrow-up";
        }else{
          arrowdiv[key].className="arrow-div arrow-down";
        }
      });



    }
  });
}


function parseDate(date) {
  var m = /^(\d{4})-(\d\d)-(\d\d) (\d\d):(\d\d):(\d\d)/.exec(date);
  var tzOffset = new Date(+m[1], +m[2] - 1, +m[3], +m[4], +m[5], +m[6]).getTimezoneOffset();

  return new Date(+m[1], +m[2] - 1, +m[3], +m[4], +m[5] - tzOffset, +m[6]);
}

function converter(e){
  if(e.target==converterHomeCurrency){
    converterHostCurrency.value = (converterHomeCurrency.value/window.homeRate*window.hostRate).toFixed(3);
  }else{
    converterHomeCurrency.value = (converterHostCurrency.value/window.hostRate*window.homeRate).toFixed(3);
  }
}

function colorText(elem,color){
  elem.style.color=color;
}

function checkInput(input){
  return ( availableCurrencies.indexOf(input.value) == -1 ) ? false : true;
}

function setCookie(cname,cvalue){
  var d = new Date();
  d.setTime(d.getTime()+(365*24*60*60*1000));
  var expires = "expires="+d.toGMTString();
  document.cookie = cname + "=" + cvalue + "; " + expires;
}

function getCookie(cname){
  var name = cname + "=";
  var ca = document.cookie.split(';');
  for(var i=0; i<ca.length; i++)
  {
    var c = ca[i].trim();
    if (c.indexOf(name)==0) return c.substring(name.length,c.length);
  }
  return "";
}

function checkCookie(){
  var data=getCookie("save");
  log(data);
  return data!="";
}

function log(thing){
  if(debug)
    console.log(thing);
}

function initialize() {

  google.maps.visualRefresh = true;
  var isMobile = (navigator.userAgent.toLowerCase().indexOf('android') > -1) ||
  (navigator.userAgent.match(/(iPod|iPhone|iPad|BlackBerry|Windows Phone|iemobile)/));
  if (isMobile) {
    var viewport = document.querySelector("meta[name=viewport]");
    viewport.setAttribute('content', 'initial-scale=1.0, user-scalable=no');
  }
  var mapDiv = document.getElementById('googft-mapCanvas');
  mapDiv.style.width = isMobile ? '100%' : '100%';
  mapDiv.style.height = isMobile ? '100%' : '90%';
  map = new google.maps.Map(mapDiv, {
    center: new google.maps.LatLng(0.0, 0.0),
    zoom: 2,
    mapTypeId: google.maps.MapTypeId.ROADMAP
  });
  map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(document.getElementById('googft-legend-open'));
  map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(document.getElementById('googft-legend'));


  if (isMobile) {
    var legend = document.getElementById('googft-legend');
    var legendOpenButton = document.getElementById('googft-legend-open');
    var legendCloseButton = document.getElementById('googft-legend-close');
    legend.style.display = 'none';
    legendOpenButton.style.display = 'block';
    legendCloseButton.style.display = 'block';
    legendOpenButton.onclick = function() {
      legend.style.display = 'block';
      legendOpenButton.style.display = 'none';
    }
    legendCloseButton.onclick = function() {
      legend.style.display = 'none';
      legendOpenButton.style.display = 'block';
    }
  }

  $(document).on('opened', '[data-reveal]', function () {
    log("reveal opened");
    google.maps.event.trigger(window.map, 'resize');
    window.map = new google.maps.Map(mapDiv, {
      center: new google.maps.LatLng(20.0, 0.0),
      zoom: 2,
      mapTypeId: google.maps.MapTypeId.ROADMAP
    });
    /*layer.setOptions({
      query: {
        select: "col14",
        from: "1brA30s0zsBBH3u6NFn8Lk0i4V31W3fzvSEStct7g",
        where: "col3 in (\x27"+ window.homeCurrency +"\x27, \x27"+ window.hostCurrency +"\x27)"
      }
    });*/
  layer = new google.maps.FusionTablesLayer({
    map: window.map,
    heatmap: { enabled: false },
    query: {
      select: "col7",
      from: "1brA30s0zsBBH3u6NFn8Lk0i4V31W3fzvSEStct7g",
      where: "col3 in (\x27"+ window.hostCurrency +"\x27)"
    },
    options: {
      styleId: 2,
      templateId: 2
    }
  });
});
  window.map = map;
}

google.maps.event.addDomListener(window, 'load', initialize);