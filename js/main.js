// Import the functions you need from the SDKs you need
import { initializeApp } from "https://www.gstatic.com/firebasejs/12.4.0/firebase-app.js";
import { getAuth, GoogleAuthProvider, signInWithPopup } from "https://www.gstatic.com/firebasejs/12.4.0/firebase-auth.js";

// Your web app's Firebase configuration
const firebaseConfig = {
    apiKey: "AIzaSyC5TtXG5M_RgCveKBxuZhxVM2VYBvxGFrY",
    authDomain: "login-2063b.firebaseapp.com",
    projectId: "login-2063b",
    storageBucket: "login-2063b.firebasestorage.app",
    messagingSenderId: "515617354329",
    appId: "1:515617354329:web:8f9f894bf3b681c5537748"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
auth.languageCode = 'en';
const provider = new GoogleAuthProvider();

const googleLogin = document.getElementById("google-login-btn");
if (googleLogin) {
    googleLogin.addEventListener("click", function() {
        console.log('Google login button clicked');
        
        // Show loading state
        googleLogin.disabled = true;
        googleLogin.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
        
        signInWithPopup(auth, provider)
            .then(async (result) => {
                console.log('✅ Google Sign-In Successful');
                const user = result.user;
                
                // Get the ID token
                const idToken = await user.getIdToken();
                
                console.log('User Info:', {
                    email: user.email,
                    name: user.displayName,
                    uid: user.uid
                });
                
                // Send token to PHP backend for verification
                try {
                    console.log('📤 Sending data to google_auth.php...');
                    
                    const response = await fetch('google_auth.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            idToken: idToken,
                            email: user.email,
                            name: user.displayName,
                            photoURL: user.photoURL,
                            uid: user.uid
                        })
                    });
                    
                    console.log('Response status:', response.status);
                    
                    // Check if response is JSON
                    const contentType = response.headers.get("content-type");
                    if (!contentType || !contentType.includes("application/json")) {
                        const text = await response.text();
                        console.error('❌ Non-JSON response:', text);
                        alert('Server error: Expected JSON response but got: ' + text.substring(0, 100));
                        googleLogin.disabled = false;
                        googleLogin.innerHTML = '<img src="https://www.gstatic.com/images/branding/product/1x/gsa_512dp.png" alt="Google" class="google-icon"> Login with Google';
                        return;
                    }
                    
                    const data = await response.json();
                    console.log('📥 Response from server:', data);
                    
                    if (data.success) {
                        console.log('✅ Backend authentication successful');
                        if (data.needsRole) {
                            console.log('→ Redirecting to choose_role.php');
                            window.location.href = 'choose_role.php';
                        } else {
                            console.log('→ Redirecting to dashboard');
                            window.location.href = 'index.php?page=dashboard';
                        }
                    } else {
                        console.error('❌ Backend authentication failed:', data.message);
                        alert('Login failed: ' + data.message);
                        googleLogin.disabled = false;
                        googleLogin.innerHTML = '<img src="https://www.gstatic.com/images/branding/product/1x/gsa_512dp.png" alt="Google" class="google-icon"> Login with Google';
                    }
                } catch (error) {
                    console.error('❌ Error sending to backend:', error);
                    alert('Network error: ' + error.message + '. Please check if google_auth.php exists and is accessible.');
                    googleLogin.disabled = false;
                    googleLogin.innerHTML = '<img src="https://www.gstatic.com/images/branding/product/1x/gsa_512dp.png" alt="Google" class="google-icon"> Login with Google';
                }
            })
            .catch((error) => {
                console.error('❌ Google Sign-In Error:', error);
                console.error('Error Code:', error.code);
                console.error('Error Message:', error.message);
                
                let errorMessage = 'Google sign-in failed. ';
                
                // Provide specific error messages
                switch(error.code) {
                    case 'auth/popup-closed-by-user':
                        errorMessage += 'You closed the sign-in window. Please try again.';
                        break;
                    case 'auth/popup-blocked':
                        errorMessage += 'Pop-up was blocked by your browser. Please allow pop-ups for this site.';
                        break;
                    case 'auth/cancelled-popup-request':
                        errorMessage += 'Sign-in was cancelled. Please try again.';
                        break;
                    case 'auth/network-request-failed':
                        errorMessage += 'Network error. Please check your internet connection.';
                        break;
                    case 'auth/internal-error':
                        errorMessage += 'Internal error. Please check Firebase configuration.';
                        break;
                    case 'auth/unauthorized-domain':
                        errorMessage += 'This domain is not authorized. Please add it to Firebase Console.';
                        break;
                    default:
                        errorMessage += error.message;
                }
                
                alert(errorMessage);
                googleLogin.disabled = false;
                googleLogin.innerHTML = '<img src="https://www.gstatic.com/images/branding/product/1x/gsa_512dp.png" alt="Google" class="google-icon"> Login with Google';
            });
    });
}