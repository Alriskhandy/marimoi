// Smooth scrolling for navigation links
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute("href"));
        if (target) {
            target.scrollIntoView({
                behavior: "smooth",
                block: "start",
            });
        }
    });
});

// Scroll animation
const animateOnScroll = () => {
    const elements = document.querySelectorAll(".animate-on-scroll");
    elements.forEach((element) => {
        const elementTop = element.getBoundingClientRect().top;
        const elementVisible = 150;

        if (elementTop < window.innerHeight - elementVisible) {
            element.classList.add("animated");
        }
    });
};

window.addEventListener("scroll", animateOnScroll);
window.addEventListener("load", animateOnScroll);

// Animate counter numbers
function animateStatNumbers() {
    const counters = document.querySelectorAll(".stat-number");
    counters.forEach((counter) => {
        const target = parseInt(counter.getAttribute("data-target"));
        const increment = target / 100;
        let current = 0;

        counter.textContent = "0";

        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                counter.textContent = target;
                clearInterval(timer);
            } else {
                counter.textContent = Math.floor(current);
            }
        }, 30);
    });
}

// Auto-animate counters when stats section is visible
const statsSection = document.querySelector("#stats");
const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
        if (entry.isIntersecting) {
            animateStatNumbers();
            observer.unobserve(entry.target);
        }
    });
});

if (statsSection) {
    observer.observe(statsSection);
}

// Typing animation
const typingWords = [
    "Proyek Strategis Daerah",
    "Proyek Strategis Nasional",
    "Usulan Musrenbang",
    "Pokir DPRD",
];
let currentWordIndex = 0;
let currentCharIndex = 0;
let isDeleting = false;
let typingSpeed = 100;

function typeWriter() {
    const typingElement = document.getElementById("typingText");
    const currentWord = typingWords[currentWordIndex];

    if (isDeleting) {
        typingElement.textContent = currentWord.substring(
            0,
            currentCharIndex - 1
        );
        currentCharIndex--;
        typingSpeed = 50;
    } else {
        typingElement.textContent = currentWord.substring(
            0,
            currentCharIndex + 1
        );
        currentCharIndex++;
        typingSpeed = 100;
    }

    if (!isDeleting && currentCharIndex === currentWord.length) {
        setTimeout(() => {
            isDeleting = true;
        }, 2000);
    } else if (isDeleting && currentCharIndex === 0) {
        isDeleting = false;
        currentWordIndex = (currentWordIndex + 1) % typingWords.length;
    }

    setTimeout(typeWriter, typingSpeed);
}

// Start typing animation when page loads
window.addEventListener("load", function () {
    setTimeout(typeWriter, 1000);
});

// Project filtering
// function filterProjects(kategori) {
//     const cards = document.querySelectorAll(".project-card");
//     const buttons = document.querySelectorAll(".status-btn");

//     // Update active button
//     buttons.forEach((btn) => btn.classList.remove("active"));
//     event.target.classList.add("active");

//     // Filter cards
//     cards.forEach((card) => {
//         if (card.getAttribute("data-status") === kategori) {
//             card.style.display = "block";
//             card.style.animation = "fadeIn 0.5s ease";
//         } else {
//             card.style.display = "none";
//         }
//     });
// }


// Floating Action Button
let fabMenuOpen = false;

function toggleFabMenu() {
    const fabMenu = document.getElementById("fabMenu");
    const fabMain = document.querySelector(".fab-main i");

    fabMenuOpen = !fabMenuOpen;

    if (fabMenuOpen) {
        fabMenu.classList.add("active");
        fabMain.style.transform = "rotate(45deg)";
    } else {
        fabMenu.classList.remove("active");
        fabMain.style.transform = "rotate(0deg)";
    }
}

function reportIssue() {
    alert(
        "Fitur pelaporan masalah akan segera tersedia!\n\nAnda dapat melaporkan masalah infrastruktur melalui platform ini."
    );
    toggleFabMenu();
}

function halamanAspirasi() {
    window.location.href = '/aspirasi-masyarakat';
    toggleFabMenu();
}

function halamanFAQ() {
    window.location.href = '/faq';
    toggleFabMenu();
}


// Navbar background on scroll
window.addEventListener("scroll", function () {
    const navbar = document.querySelector(".navbar");
    if (window.scrollY > 50) {
        navbar.style.background = "rgba(255, 255, 255, 0.98)";
        navbar.style.boxShadow = "0 2px 20px rgba(0,0,0,0.1)";
    } else {
        navbar.style.background = "rgba(255, 255, 255, 0.95)";
        navbar.style.boxShadow = "none";
    }
});

// Feature card interactions
document.querySelectorAll(".feature-card").forEach((card) => {
    card.addEventListener("click", function () {
        const link = this.querySelector("a").href;
        window.location.href = link;
    });
});

// Project card interactions
document.querySelectorAll(".project-card").forEach((card) => {
    card.addEventListener("click", function () {
        const title = this.querySelector(".project-title").textContent;
        const status = this.querySelector(".project-status").textContent;
        const location = this.querySelector(
            ".project-location"
        ).textContent.replace("📍 ", "");
        const progress = this.querySelector(".progress-text").textContent;

        alert(
            `Detail Proyek:\n\nNama: ${title}\nStatus: ${status}\nLokasi: ${location}\nProgress: ${progress}\n\nKlik "Lihat Detail" untuk informasi lengkap.`
        );
    });
});

// Add loading animation
window.addEventListener("load", function () {
    document.body.style.opacity = "0";
    document.body.style.transition = "opacity 0.5s ease";
    setTimeout(() => {
        document.body.style.opacity = "1";
    }, 100);
});

// Close fab menu when clicking outside
document.addEventListener("click", function (event) {
    const fab = document.querySelector(".floating-actions");
    if (!fab.contains(event.target) && fabMenuOpen) {
        toggleFabMenu();
    }
});

// Enhanced filterProjects function
function filterProjects(event, status) {
    // Remove active class from all buttons
    document.querySelectorAll('.status-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Add active class to clicked button
    event.target.classList.add('active');
    
    // Filter project cards
    const projectCards = document.querySelectorAll('.project-card');
    projectCards.forEach(card => {
        const cardStatus = card.getAttribute('data-status');
        if (status === 'all' || cardStatus === status) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

document.addEventListener("DOMContentLoaded", function () {
            // Show only PSD projects initially
    const projectCards = document.querySelectorAll('.project-card');
    projectCards.forEach(card => {
        const status = card.getAttribute('data-status');
        if (status === 'psd') {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
    
    // Set PSD button as active
    const psdBtn = document.querySelector(".status-btn[onclick*='psd']");
    if (psdBtn) {
        // Remove active class from all buttons
        document.querySelectorAll('.status-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        // Add active class to PSD button
        psdBtn.classList.add('active');
    }
});
