document.addEventListener('DOMContentLoaded', function() {
    const copyButtons = document.querySelectorAll('.js-copy');
    
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.closest('.form-copy').querySelector('input');
            const iconCopy = this.querySelector('.icon-copy2');
            const iconCheck = this.querySelector('.icon-check');
            
            input.select();
            document.execCommand('copy');
            
            iconCopy.classList.add('d-none');
            iconCheck.classList.remove('d-none');
            
            setTimeout(() => {
                iconCopy.classList.remove('d-none');
                iconCheck.classList.add('d-none');
            }, 2000);
        });
    });
});