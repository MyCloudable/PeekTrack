import {ref} from 'vue'

function TimeConvert(n){

    // Store the input number of minutes in a variable num
    let num = n;
    // Calculate the total hours by dividing the number of minutes by 60
    let hours = (num / 60);
    // Round down the total hours to get the number of full hours
    let rhours = Math.floor(hours);
    // Calculate the remaining minutes after subtracting the full hours from the total hours
    let minutes = (hours - rhours) * 60;
    // Round the remaining minutes to the nearest whole number
    let rminutes = Math.round(minutes);
    // Construct and return a string representing the conversion result
    // return num + " minutes = " + rhours + " hour(s) and " + rminutes + " minute(s).";
    return rhours + "h " + rminutes + "m";

}

export default TimeConvert