import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.css";
import "../../scss/components/_flatpickr.scss";

flatpickr("#dateRangePicker", {
    // mode: "range",
    dateFormat: "Y-m-d", // Customize your date format
    // You can apply themes or write custom CSS to style the calendar
    // Flatpickr has its own CSS that you'd need to override/customize.
    onOpen: function (selectedDates, dateStr, instance) {
        instance.element
            .closest(".data-control-date-picker")
            .classList.add("active");
    },
    onClose: function (selectedDates, dateStr, instance) {
        instance.element
            .closest(".data-control-date-picker")
            .classList.remove("active");
    },
});
