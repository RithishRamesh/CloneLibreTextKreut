"use strict";Object.defineProperty(exports,"__esModule",{value:!0}),exports.redirectOnLogin=redirectOnLogin,exports.formatDate=void 0;var formatDate=function(e){var t=new Date(e.replace(" ","T"));return["January","February","March","April","May","June","July","August","September","October","November","December"][t.getMonth()]+" "+t.getDate()+", "+t.getFullYear()};function redirectOnLogin(e,t){var r="students"==={2:"instructors",3:"students",4:"tas"}[e.getters["auth/user"].role]?"students.courses.index":"instructors.courses.index";t.push({name:r})}exports.formatDate=formatDate;
