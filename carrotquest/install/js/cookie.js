// для работы с куками
(function () {
	if (typeof cookie == 'undefined')
	{
		Cookie = {
			// возвращает cookie если есть или undefined
			get: function (name) {
				var matches = document.cookie.match(new RegExp(
				  "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
				))
				return matches ? decodeURIComponent(matches[1]) : undefined 
			},

			// уcтанавливает cookie
			set: function (name, value, props) {
				props = props || {}
				var exp = props.expires
				if (typeof exp == "number" && exp) {
					var d = new Date()
					d.setTime(d.getTime() + exp*1000)
					exp = props.expires = d
				}
				if(exp && exp.toUTCString) { props.expires = exp.toUTCString() }

				value = encodeURIComponent(value)
				var updatedCookie = name + "=" + value
				for(var propName in props){
					updatedCookie += "; " + propName
					var propValue = props[propName]
					if(propValue !== true){ updatedCookie += "=" + propValue }
				}
				document.cookie = updatedCookie

			},

			// удаляет cookie
			delete: function (name) {
				setCookie(name, null, { expires: -1 })
			}
		};
	}
	else
		console.log('Object cookie is already defined: ',Cookie);
}) ();