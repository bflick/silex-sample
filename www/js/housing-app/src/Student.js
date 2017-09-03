import React, { Component } from 'react';

class Student extends Component {

    constructor(props) {
        super(props);
        this.state = {
            student: this.props.bedroom.student||{id: 0},
            displayInput: 'name',
        };

        //@todo getAllStudents from datastore
    }

    onChangeStudent(event) {
        // @todo prompt with a question about where to put old student
        // @todo debounce timeout
        var newStudent = this.props.dataStore.findStudent(event.target['data-studentId']);
        if (event.target.id) {
            switch (event.target.id) {
            case 'student-name':
                newStudent.name = event.target.value;
                break;
            case 'student-dob':
                newStudent.date_of_birth = event.target.value;
                break;
            case 'student-id':
                newStudent.student_id = event.target.value;
                break;
            case 'student-address':
                newStudent.address = event.target.value;
                break;
            case 'student-phone':
                newStudent.phone = event.target.value;
                break;
            case 'student-gender':
                newStudent.gender = event.target.value;
                break;
            default:
                console.log("Bad edits made for student " + this.state.student.name);
                break;
            }
        }

        this.setState(function(prevState, currentProps) {
            return {
                student: newStudent,
                displayInput: prevState.displayInput,
            };
        });

        var data = this.props.dataStore.data;
        for (var d in data) {
            var dorm = data[d];
            for (var b in dorm.bedrooms) {
                var br = dorm.bedrooms[b];
                if (this.props.bedroom.id == br.id
                    && br.student.id != this.state.student.id) {
                    br.student = this.state.student;
                }
            }
        }

        // @todo do this in an onSubmit fn
        this.props.dataStore.sendMessage(JSON.stringify(this.props.dataStore.data));
    }

    onChangeVisibility(event) {
        var newDisplayInput = event.target.value;
        this.setState(function(prevState, currentProps) {
            return {
                student: prevState.student,
                displayInput: newDisplayInput,
            };
        });
    }

    render() {
        var self = this;
        const onChangeVisibility = self.onChangeVisibility.bind(self);

        const hiddenStyle = {visibility: 'hidden'};
        const visibleStyle = {visibility: 'visible'};
        var nameStyle = hiddenStyle;
        var dobStyle = hiddenStyle;
        var addressStyle = hiddenStyle;
        var phoneStyle = hiddenStyle;
        var genderStyle = hiddenStyle;
        var idStyle = hiddenStyle;

        switch (this.state.displayInput) {
        case 'name':
            nameStyle = visibleStyle;
            break;
        case 'dob':
            dobStyle = visibleStyle;
            break;
        case 'address':
            addressStyle = visibleStyle;
            break;
        case 'phone':
            phoneStyle = visibleStyle;
            break;
        case 'gender':
            genderStyle = visibleStyle;
            break;
        case 'id':
            idStyle = visibleStyle;
        }
        return (<div>
                <div style={nameStyle}>
                <label for="student-name">Name:</label>
                <input id="student-name" type="text" onChange={self.onChangeStudent.bind(self)} data-studentId={self.state.student.id}/>
                </div>

                <div style={dobStyle}>
                <label for="student-dob"> DOB:</label>
                <input id="student-dob" type="text" onChange={self.onChangeStudent.bind(self)} data-studentId={self.state.student.id}/>
                </div>

                <div style={addressStyle}>
                <label for="student-address"> Address:</label>
                <input id="student-address" type="text" onChange={self.onChangeStudent.bind(self)} data-studentId={self.state.student.id}/>
                </div>

                <div  style={phoneStyle}>
                <label for="student-phone"> Phone:</label>
                <input id="student-phone" type="text" onChange={self.onChangeStudent.bind(self)} data-studentId={self.state.student.id}/>
                </div>
                
                <div style={genderStyle}>
                <label for="student-gender"> Gender:</label>
                <input id="student-gender" type="text" onChange={self.onChangeStudent.bind(self)} data-studentId={self.state.student.id}/>
                </div>

                <div style={idStyle}>
                <label for="student-id"> Id#:</label>
                <input id="student-id" type="text" onChange={self.onChangeStudent.bind(self)} data-studentId={self.state.student.id}/>
                </div>  
                <select onChange={onChangeVisibility} value={self.state.displayInput}>
                <option value="name">Name</option>
                <option value="dob">Dob</option>
                <option value="address">Address</option>
                <option value="phone">Phone</option>
                <option value="gender">Gender</option>
                <option value="id">Id#</option>
                </select>
                </div>
        );
    }
}

export default Student;
