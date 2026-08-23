document.addEventListener('livewire:init', () => {
    window.Livewire.interceptRequest(({ onError }) => {
        onError(({ response, preventDefault }) => {
            if (response.status !== 419) {
                return;
            }

            preventDefault();
            window.location.reload();
        });
    });
});
