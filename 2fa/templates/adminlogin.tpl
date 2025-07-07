{assign var="title" value="2FA Login"}
{extends file="layout.tpl"}


{block name="pagecss"}
.method-btn.active {
    border: 2px solid #0d6efd; /* Bootstrap primary */
    background-color: #d0e7ff; /* a bit deeper blue, less white */
    color: #0a3d7c; /* darker blue for text and icon */
}

.method-btn.active i {
    color: #0a3d7c; /* same darker blue for the icon */
}
.disabled-label {
    opacity: 0.6;
    cursor: not-allowed;
}
button:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}
{/block}


{block name="content"}
    <div class="container mt-5">
        <h2 class="mb-4 text-center">Two-Factor Authentication</h2>

        {if $error}
            <div class="alert alert-danger">{$error}</div>
        {/if}

<div class="d-flex justify-content-center flex-wrap gap-3 mb-4">
    {foreach $providers as $key => $provider}
        <button type="button"
                class="btn btn-outline-primary text-start method-btn"
                data-method="{$key}"
                title="{$provider->getLabel()}"
                style="min-width: 200px; padding: 1rem; display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
            <i class="bi {$provider->getIcon()}" style="font-size: 1.5rem;"></i>
            <span>{$provider->getLabel()}</span>
        </button>
    {/foreach}
</div>

        <form id="auth-form" method="POST" autocomplete="off" class="mx-auto" style="max-width: 400px;">
            <input type="hidden" name="_csrf_token" value="{$csrf_token}" />
            <input type="hidden" name="method" id="auth-method" value="" />
            <div id="form-fields-container"></div>
            <button type="submit" disabled=true id="verify-btn" class="btn btn-success w-100 mt-3 disabled" disabled=true>Verify</button>
        </form>
    </div>

    <script>
        const providers = {$providers_json nofilter};

        const formContainer = document.getElementById('form-fields-container');
        const methodInput = document.getElementById('auth-method');

        const renderForm = (method) => {
            methodInput.value = method;
            formContainer.innerHTML = '';

            const fields = providers[method].form.fields;
            for (const field of fields) {
                const wrapper = document.createElement('div');
                wrapper.classList.add('mb-3');

                 if (field.type === 'button') {
                      // Render a button for sending code
                      const btn = document.createElement('button');
                      btn.type = 'button';
                      btn.textContent = field.buttonText || 'Send';
                      btn.classList.add('btn', 'btn-secondary', 'w-100');
                      btn.id = field.name;

                      wrapper.appendChild(btn);

                      // Inject JS event listener if provided
                      if (field.jsEventListener) {
                          // Create a function from the string, then bind it to button
                          const listenerFunc = new Function('event', field.jsEventListener + '\nsendCodeHandler(event);');
                          btn.addEventListener('click', listenerFunc);
                      }

                  } else {
                      const label = document.createElement('label');
                      label.classList.add('form-label');
                      label.id = field.name+'-lbl';
                      label.htmlFor = field.name;
                      label.textContent = field.label;
                      if (field.disabled) {
                          label.classList.add('disabled-label');
                      }

                      const input = document.createElement('input');
                      input.type = field.type;
                      input.name = field.name;
                      input.id = field.name;
                      input.required = field.required || false;
                      input.classList.add('form-control');

                      if (field.autocomplete) {
                          input.autocomplete = field.autocomplete;
                      } else {
                          input.autocomplete = 'off';
                      }

                      if (field.pattern) {
                          input.pattern = field.pattern;
                      }

                      if (field.disabled) {
                          input.disabled = true;
                      }

                      wrapper.appendChild(label);
                      wrapper.appendChild(input);
                  }
                  formContainer.appendChild(wrapper);
              }

              // Disable Verify button initially if code input is disabled
              const codeInput = formContainer.querySelector('input[name="code"]');
              const verifyBtn = document.getElementById('auth-form').querySelector('button[type="submit"]');
              verifyBtn.disabled = codeInput?.disabled || false;
        };

        document.querySelectorAll('.method-btn').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.method-btn').forEach(b => b.classList.remove('active'));
                button.classList.add('active');
                renderForm(button.dataset.method);
            });
        });

        // Auto-select first provider on page load
        window.addEventListener('DOMContentLoaded', () => {
            const firstMethod = Object.keys(providers)[0];
            const firstButton = document.querySelector('.method-btn[data-method="' + firstMethod + '"]');
            if (firstButton) firstButton.click();
        });

         function showToast(message, background = '#28a745') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.backgroundColor = background;
            toast.className = "show";
            setTimeout(() => {
              toast.className = toast.className.replace("show", "");
            }, 3000);
          }
    </script>
{/block}

