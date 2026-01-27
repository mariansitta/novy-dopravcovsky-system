@if(session('message') && session('type'))
    <div class="alert alert-{{ session('type') }} alert-dismissible fade show" role="alert">
        {!! session('message') !!}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
