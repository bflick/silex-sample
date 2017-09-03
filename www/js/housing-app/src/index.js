import React from 'react';
import ReactDOM from 'react-dom';
import './index.css';
import App from './App';
import DataStore from './DataStore';
import registerServiceWorker from './registerServiceWorker';

const socket = new WebSocket('ws://127.0.0.1:25569');

socket.onopen = function() {
    socket.send(JSON.stringify([5, 'sub-dormatory']));
};

socket.onmessage = function(message) {
    // every time an update has been published, new results should appear.
    console.log(message);
    if (message.data[0] === 8 && message.data[1] === 'sub-dormatory') {
        dataStore.setData(JSON.parse(message.data[3]));
    }
};

const dataStore = new DataStore();

ReactDOM.render(<App dataStore={dataStore}/>, document.getElementById('root'));

// service worker only available to secure origins
// registerServiceWorker();

