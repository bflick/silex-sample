import React, { Component } from 'react';
import Student from './Student';

import './App.css';

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
	
        var dormPostRequest = new Request('/dormatories', {
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
        const bedroom2 = this.props.dataStore.getBedroom(2, this.state.dormatory, this.state.floor);
        const bedroom3 = this.props.dataStore.getBedroom(3, this.state.dormatory, this.state.floor);
        const bedroom4 = this.props.dataStore.getBedroom(4, this.state.dormatory, this.state.floor);
        const bedroom5 = this.props.dataStore.getBedroom(5, this.state.dormatory, this.state.floor);
        const bedroom6 = this.props.dataStore.getBedroom(6, this.state.dormatory, this.state.floor);
        const bedroom7 = this.props.dataStore.getBedroom(7, this.state.dormatory, this.state.floor);
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
            <Student dataStore={this.props.dataStore} bedroom={bedroom2} />
            </div>                                                       
            <div className="three">                                      
            <Student dataStore={this.props.dataStore} bedroom={bedroom3} />
            </div>                                                       
            <div className="four">                                       
            <Student dataStore={this.props.dataStore} bedroom={bedroom4} />
            </div>                                                       
            <div className="five">                                       
            <Student dataStore={this.props.dataStore} bedroom={bedroom5} />
            </div>                                                       
            <div className="six">                                        
            <Student dataStore={this.props.dataStore} bedroom={bedroom6} />
            </div>                                                       
            <div className="seven">                                      
            <Student dataStore={this.props.dataStore} bedroom={bedroom7} />
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
