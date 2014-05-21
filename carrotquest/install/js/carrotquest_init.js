(function()
{ 
  if (typeof carrotquest === 'undefined') {
    var s = document.createElement('script'); s.type = 'text/javascript'; s.async = true; 
    s.src = 'http://cdn.carrotquest.io/api.nowidgets.js'; var x = document.getElementsByTagName('head')[0]; x.appendChild(s);

    carrotquest = {}; window.carrotquestasync = []; 
	m = ['connect', 'actionButton', 'priceButton', 'cart', 'track', 'fetchMessages', 'trackBasketAdd', 'trackOrder', 'identify'];
    function Build(name, args){return function(){window.carrotquestasync.push(name, arguments);} }
    for (var i = 0; i < m.length; i++) carrotquest[m[i]] = Build(m[i]);
  };
})();

