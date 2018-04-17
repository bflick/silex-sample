import React, { Component } from 'react';
import Student from './Student';

import './App.css';
import ChatBox from './ChatBox.js';

class App extends Component {

    //@todo state of pagination/floor view
    constructor(props) {
        super(props);
        var dorm;
        var floor = 1;
        for (var d in this.props.dataStore.data) {
            dorm = this.props.dataStore.data[d];
            break;
        }
        // @todo update dorm/floor with a side scroll selector
        this.state = {
            dormatory: dorm||{},
            floor: floor,
        };
    }

    /**
     * @todo Verify all genders are the same.
     * @todo Make up input data if dorms are not 50% full
    */
    onSubmit(event) {
        event.preventDefault();
	
        var dormPostRequest = new Request(process.env.REACT_APP_HOUSING_API+'dormatories', {
            method: 'POST',
            body: JSON.stringify(this.props.dataStore.data),
        });
        return fetch(dormPostRequest)
            .then(function(response) {
                if (response.status !== 200) {
                    throw new Error('Dorm updates have not been submitted.');
                }
                return response.json(); // should just say success=>true
            });
    }

    render() {
        const bedroom1 = this.props.dataStore.getBedroom(1, this.state.dormatory, this.state.floor);
        const bedroom8 = this.props.dataStore.getBedroom(8, this.state.dormatory, this.state.floor);

        return (
            <div className="App">
            <div className="App-header">
            <h2>Student Housing</h2>
            </div>
            <form onSubmit={this.onSubmit.bind(this)}>
            <button type="submit">Submit</button>
            <div className="wrapper">
            <div className="one">
            <Student dataStore={this.props.dataStore} bedroom={bedroom1} />
            </div>
            <div className="two">
                <ChatBox socket={this.props.dataStore.socket} />
            </div>         
            <div className="eight">
            <Student dataStore={this.props.dataStore} bedroom={bedroom8} />
            </div>
            </div>
            </form>
            </div>
        );
    }
}

export default App;
