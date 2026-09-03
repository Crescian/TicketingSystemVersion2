{{-- Hard-blocks anyone flagged must_change_password (set by
     UserManagementController::store()/resetPassword() for new/reset accounts):
     no close button, static backdrop, no Escape-to-close. The actual enforcement
     is server-side (App\Http\Middleware\RequirePasswordChange redirects every
     other route to /profile), so in normal use this only ever renders as a
     locked confirmation on pages the middleware still lets through — this modal
     is defense-in-depth, not the primary gate. Scoped to the Employee role only
     — internal/staff roles are still mid-testing and shouldn't be nagged while
     that's ongoing. Include once per authenticated layout. Styled with inline
     CSS vars only (no dependency on any one layout's local classes) so it
     renders consistently whether the page extends layouts.app, layouts.admin,
     or layouts.executive. --}}
@auth
    @php
        // Skip on the profile page itself — it already shows this same warning
        // inline above the password form, so a modal on top of it would just
        // cover the very form the user is there to fill in.
        $usingDefaultPassword = Auth::user()->role?->role_name === 'Employee'
            && !request()->routeIs('profile')
            && Auth::user()->must_change_password;
    @endphp
    @if($usingDefaultPassword)
        <div class="modal fade" id="defaultPasswordModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius:20px;overflow:hidden;border:none">
                    <div style="background:var(--gd);padding:20px 28px">
                        <div style="font-family:'Nunito',sans-serif;font-weight:900;font-size:18px;color:#fff">
                            <i class="bi bi-shield-exclamation me-2" style="color:var(--yg)"></i>Secure Your Account
                        </div>
                    </div>
                    <div class="modal-body px-4 py-4">
                        <div style="background:#fff4cc;border:1px solid #f0d878;border-radius:12px;padding:14px 16px;display:flex;gap:10px;align-items:flex-start">
                            <i class="bi bi-exclamation-triangle-fill" style="color:#8a6d00;font-size:18px;margin-top:1px"></i>
                            <div style="font-size:13.5px;color:#5a4700;font-weight:600;line-height:1.5">
                                Your account requires a password change. For your account's security,
                                please change your password immediately.
                            </div>
                        </div>
                    </div>
                    <div class="px-4 py-3 d-flex justify-content-end" style="border-top:1px solid var(--bd)">
                        <a href="{{ route('profile') }}#pwSection"
                           style="background:var(--gd);color:var(--yg);font-family:'Nunito',sans-serif;font-weight:900;font-size:14px;padding:11px 28px;border-radius:50px;border:none;text-decoration:none;display:inline-flex;align-items:center;gap:6px">
                            <i class="bi bi-shield-lock"></i>Change Password Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <script>
            // Deferred to DOMContentLoaded (plain JS, not $(fn)) because this component
            // can render inside @yield('modals'), which the layout outputs before the
            // bootstrap <script src> tag near the end of <body>.
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal('#defaultPasswordModal').show();
            });
        </script>
    @endif
@endauth
