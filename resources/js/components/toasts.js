import { Toast } from "bootstrap";

document.addEventListener("DOMContentLoaded", function () {
    console.log("Initializing toasts..."); // Log for debugging

    document.querySelectorAll(".toast").forEach((toastEl) => {
        try {
            console.log("Initializing toast:", toastEl);
            const toast = new Toast(toastEl); // Use the explicitly imported Toast
            toast.show();
        } catch (error) {
            console.error("Failed to initialize toast:", toastEl, error);
        }
    });
});

// Импорт Bootstrap JS (если еще не сделан)

// Инициализация тостов при загрузке страницы
document.addEventListener("DOMContentLoaded", () => {
    const toastElList = [].slice.call(document.querySelectorAll(".toast"));
    const toastList = toastElList.map(function (toastEl) {
        // Создаем экземпляр и сразу показываем
        console.log("toastEl", toastEl);
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
        return toast; // Можно вернуть для дальнейшего управления, если нужно
    });
});
