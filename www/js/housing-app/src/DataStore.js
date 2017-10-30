class DataStore {
    constructor() {
        this.data = {};
    }

    setData(data) {
        for(var d in data) {
            var dat = data[d]
            if (dat.id) {
                this.data[dat.id] = dat;
            } else {
                throw new Error("Dormatory should have an id.")
            }
        }
    }

    getBedroom(num, dorm, floor) {
        if (dorm.bedrooms) {
            for (var b in dorm.bedrooms) {
                var suite = dorm.bedrooms[b];
                if (suite.number == num && suite.floor == floor) {
                    return suite;
                }
            }
        }
        return {student: null};
    }

    findStudent(studentId) {
        if(!this.students) {
            return this.allStudents().then(function() {
                var stu = this.students[studentId];
                return stu;
            });
        }
        return null;
    }

    allStudents() {
        var self = this;
        var studentRequest = new Request(process.env.REACT_APP_HOUSING_API+'students');
        return fetch(studentRequest)
            .then(function(response) {
                if(response.status == 200) return response.json();
                else throw new Error('Something went wrong on api server!');
            })
            .then(function(responseJson) {
                self.students = {};
                for (var s in responseJson) {
                    var stu = responseJson[s];
                    self.students[stu.id] = stu;
                }
                return self.students;
            });
    }
}

export default DataStore;
