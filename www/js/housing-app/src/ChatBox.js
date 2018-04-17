import React, { Component } from 'react';
import MessageBroker from './MessageBroker.js';

import './ChatBox.css';

class ChatBox extends Component {
    constructor(props) {
        super(props);
        this.messageBroker = new MessageBroker(props.socket);
        this.state = {
            status: 0,
            authorized: false,
        };
    };
    render() {
        return (<div className="chatbox" data-messageBroker={this.messageBroker}>
            <div className="content-chatbox">
                {this.messageBroker.formatChat()}
            </div>
            <div className="input-chatbox">
                <textarea></textarea>
            </div>
            <div className="submit-chatbox">
                <button onClick={this.sendChat} className="submit-chatbox-button">Send</button>
            </div>
        </div>);
    };
    sendChat(event) {
        this.setState({status: 1});
        this.messageBroker.handleEvent(event);
    };
}

export default ChatBox;
