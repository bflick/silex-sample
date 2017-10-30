import React from 'react';
import ReactDOM from 'react-dom';
import './index.css';
import App from './App';
import DataStore from './DataStore';
import registerServiceWorker from './registerServiceWorker';

const socket = new WebSocket('ws://sample/websocket');
const dataStore = new DataStore();

socket.onopen = function() {
    var dormInitialRequest = new Request(process.env.REACT_APP_HOUSING_API+'dormatories');
    fetch(dormInitialRequest).then(function(response) {
	if (response.status !== 200) {
	    throw new Error('Initial dorm response failed');
	}
	dataStore.setData(response.json());
    });

    // subscribe to the topic
    socket.send(JSON.stringify([5, 'sub-dormatory']));
};

socket.onmessage = function(message) {
    // every time an update has been published, new results should appear.
    console.log(message);
    if (message.data[0] === 8 && message.data[1] === 'sub-dormatory') {
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
	    dataStore.setData(data);
	}
    }
};

ReactDOM.render(<App dataStore={dataStore}/>, document.getElementById('root'));

// service worker only available to secure origins
// registerServiceWorker();

