// Thai datepicker for input[type=date] using flatpickr
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import { Thai } from "flatpickr/dist/l10n/th.js";

document.addEventListener("DOMContentLoaded", function() {
    flatpickr("input[type=date]", {
        locale: Thai,
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "j F Y",
        allowInput: true
    });
});
