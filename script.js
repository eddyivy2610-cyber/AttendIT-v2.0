const logBox = document.querySelector('.log-box');
const loginLink = document.querySelector('.login-link');
const registerLink = document.querySelector('.register-link');
const lgnBtn = document.querySelector('.lgn-btn');


registerLink.addEventListener('click' , ()=>{
    logBox.classList.add('active');
});

loginLink.addEventListener('click' , ()=>{
    logBox.classList.remove('active');
});
lgnBtn.addEventListener('click' , ()=>{
    logBox.classList.add('active-popup');
});
