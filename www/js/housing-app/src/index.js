//import React from 'react';
//import ReactDOM from 'react-dom';
import registerServiceWorker from './registerServiceWorker.js';
//const dataStore = new DataStore(socket);
var wsUri = process.env.WEBSOCKET;
var oauthTkn = '';
var tkn = new Request(
process.env.REACT_APP_HOUSING_API+'oauth/access-token',
{
    headers: {
        "ContentType": "application/json",
    },
    method: 'POST',
    body: '{"grant_type":"password","client_id":"brianflick-sample","client_secret":"secert","username":"admin","password":"foo"}',
    mode: 'cors'
});
fetch(tkn).then(function(response) {
    if (response.status !== 200) {
	throw new Error('OAuth token request failed');
    }
    console.log(response);
    response.json().then(function(json){
        oauthTkn = json.access_token;
    }).then(function() {
        const socket = new WebSocket('ws://sample:8482?access_token='+oauthTkn);
        socket.onopen = function() {
            // subscribe to the topic
            socket.send(JSON.stringify([5, 'elevated-permissions-process']));
        };

        socket.onmessage = function(message) {
            // every time an update has been published, new results should appear.
            console.log(message);
            if (message.data[0] === 8 && message.data[1] === 'elevated-permissions-process') {
	        // message.data should be parsed json of the WAMP list protocol spec
	        // in message body field (offset 2) should be encoded json still.
	        // @todo validate format of data
	        var data = [];
	        try {
	            data = JSON.parse(message.data[3]);
	        } catch (e) {
	            console.log("invalid json back from websocket");
	            console.log(e);
	        } finally {
                    console.log(data);
                    //	    dataStore.setData(data);
	        }
            }
        };
    });
    // Add the OAuth token header to socket request
});


//ReactDOM.render(<App dataStore={dataStore}/>, document.getElementById('root'));
// service worker only available to secure origins
registerServiceWorker();

