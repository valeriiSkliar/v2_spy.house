@extends('layouts.main')

@section('title', 'Отписка от рассылки')

@section('page-content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Отписка от рассылки</h4>
                </div>
                <div class="card-body">
                    @if($isValidHash)
                    <div class="text-center mb-4">
                        <i class="fas fa-envelope-open-text fa-3x text-muted mb-3"></i>
                        <p class="lead">Вы действительно хотите отписаться от рассылки?</p>
                        <p class="text-muted">Email: {{ $user->email }}</p>
                    </div>

                    <form id="unsubscribe-form" method="POST" action="{{ route('unsubscribe.process', $hash) }}">
                        @csrf
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger btn-lg">
                                <i class="fas fa-unlink me-2"></i>
                                Да, отписаться от рассылки
                            </button>
                            <a href="{{ route('home') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Отмена
                            </a>
                        </div>
                    </form>
                    @else
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h5>Неверная ссылка</h5>
                        <p class="text-muted">
                            Ссылка для отписки недействительна или срок её действия истёк.
                            Возможно, вы уже отписались от рассылки.
                        </p>
                        <a href="{{ route('home') }}" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i>
                            На главную
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if($isValidHash)
<script>
    document.getElementById('unsubscribe-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Показываем индикатор загрузки
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Отписываем...';
    
    fetch(form.action, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                // Показываем сообщение об успехе
                form.innerHTML = `
                    <div class="alert alert-success text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h5>Отписка выполнена</h5>
                        <p>${data.message}</p>
                        <a href="{{ route('home') }}" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i>
                            На главную
                        </a>
                    </div>
                `;
            }
        } else {
            // Показываем ошибку
            const errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-danger';
            errorDiv.innerHTML = `<i class="fas fa-exclamation-circle me-2"></i>${data.message}`;
            form.insertBefore(errorDiv, form.firstChild);
            
            // Восстанавливаем кнопку
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger';
        errorDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Произошла ошибка. Попробуйте позже.';
        form.insertBefore(errorDiv, form.firstChild);
        
        // Восстанавливаем кнопку
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});
</script>
@endif
@endsection