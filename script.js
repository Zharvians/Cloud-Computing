window.addEventListener("load",()=>{

    document.getElementById("loadingScreen").style.display="none";

    // default sorting
    sortFiles();

});

const modal = document.getElementById("imgModal");
const modalImg = document.getElementById("modalImg");

function openModal(src) {
    console.log("clicked image:", src);
    modalImg.src = src;
    modal.classList.add("active");
}

// klik background atau tombol X
modal.addEventListener("click", (e) => {
    if (e.target === modal || e.target.closest(".close-modal")) {
        modal.classList.remove("active");
    }
});
    
/* ========================= */
/* 📂 FOLDER MODAL */
/* ========================= */

function openFolderModal(){

    document
    .getElementById("folderModal")
    .classList.add("active");
}

function closeFolderModal(){

    document
    .getElementById("folderModal")
    .classList.remove("active");
}

/* klik luar modal */

document
.getElementById("folderModal")
.addEventListener("click",(e)=>{

    if(e.target.id === "folderModal"){

        closeFolderModal();

    }

});
    
function toggleView(){
    document
    .getElementById("fileGrid")
    .classList.toggle("list-view");
}

// ESC close
document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
        modal.classList.remove("active");
    }
});
    
function scrollToTop(){
    window.scrollTo({top:0, behavior:"smooth"});
}

    if (window.location.search.includes("status=")) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }

            function openMenu(){
        document
        .getElementById("drawer")
        .classList.add("active");
    }

    function closeMenu(){
        document
        .getElementById("drawer")
        .classList.remove("active");
    }

    function toggleTheme(){

        document.body.classList.toggle("dark-mode");

        if(document.body.classList.contains("dark-mode")){
            localStorage.setItem("theme","dark");
        }else{
            localStorage.setItem("theme","light");
        }
    }

    if(localStorage.getItem("theme") === "dark"){
        document.body.classList.add("dark-mode");
    }
    
function toggleFilesMenu(event){

    event.stopPropagation();

    const dropdown =
    document.getElementById("filesDropdown");

    const arrow =
    document.getElementById("arrowIcon");

    dropdown.classList.toggle("active");

    arrow.classList.toggle("rotate");
}
    
function filterFiles(type, btn){

    const files =
    document.querySelectorAll("#fileGrid .file");

    files.forEach(file=>{

        const fileType =
        file.dataset.type;

        const isFolder =
        fileType === "folder";

        const isFavorite =
        file.dataset.favorite === "1";

        /* FAVORITE */

        if(type === "favorite"){

            if(isFavorite){
                file.style.display = "block";
            }else{
                file.style.display = "none";
            }

            return;
        }

        /* FOLDER */

        if(type === "folder"){

            file.style.display =
            isFolder ? "block" : "none";

            return;
        }

        /* ALL */

        if(type === "all"){

            file.style.display = "block";
            return;
        }

        /* PHOTO / DOC */

        if(
            !isFolder &&
            fileType === type
        ){

            file.style.display = "block";

        }else{

            file.style.display = "none";

        }

    });

    document.querySelectorAll(".tab-btn")
    .forEach(tab=>{
        tab.classList.remove("active");
    });

    btn.classList.add("active");
}
    
/* ========================= */
/* ✏️ RENAME MODAL */
/* ========================= */

function openRenameModal(type,id,name){

    document
    .getElementById("renameModal")
    .classList.add("active");

    document
    .getElementById("renameType")
    .value = type;

    document
    .getElementById("renameId")
    .value = id;

    document
    .getElementById("renameInput")
    .value = name;
}

function closeRenameModal(){

    document
    .getElementById("renameModal")
    .classList.remove("active");
}

document
.getElementById("renameModal")
.addEventListener("click",(e)=>{

    if(e.target.id === "renameModal"){

        closeRenameModal();

    }

});
    
/* ========================= */
/* Notif */
/* ========================= */
    
function openNotifModal(){

    document
    .getElementById("notifModal")
    .classList.add("active");
}

function closeNotifModal(){

    document
    .getElementById("notifModal")
    .classList.remove("active");
}

document
.getElementById("notifModal")
.addEventListener("click",(e)=>{

    if(e.target.id === "notifModal"){

        closeNotifModal();

    }

});
    
/* ========================= */
/* 🔃 SORT FILE */
/* ========================= */

function sortFiles(){

    const grid =
    document.getElementById("fileGrid");

    const sortValue =
    document.getElementById("sortSelect").value;

    const files =
    Array.from(document.querySelectorAll("#fileGrid .file"));

    files.sort((a,b)=>{

        const aFolder =
        a.dataset.type === "folder";

        const bFolder =
        b.dataset.type === "folder";

        /* ========================= */
        /* 📂 FILE / FOLDER PRIORITY */
        /* ========================= */

        if(sortValue === "folder_first"){

            if(aFolder && !bFolder) return -1;
            if(!aFolder && bFolder) return 1;

        }

        if(sortValue === "file_first"){

            if(!aFolder && bFolder) return -1;
            if(aFolder && !bFolder) return 1;

        }

        /* default = folder belakang */

        if(
            sortValue !== "folder_first"
            &&
            sortValue !== "file_first"
        ){

            if(aFolder && !bFolder) return 1;
            if(!aFolder && bFolder) return -1;

        }

        /* ========================= */
        /* 🔃 NORMAL SORT */
        /* ========================= */

        if(sortValue === "newest"){
            return b.dataset.date - a.dataset.date;
        }

        if(sortValue === "oldest"){
            return a.dataset.date - b.dataset.date;
        }

        if(sortValue === "name_asc"){
            return a.dataset.name.localeCompare(b.dataset.name);
        }

        if(sortValue === "name_desc"){
            return b.dataset.name.localeCompare(a.dataset.name);
        }

        if(sortValue === "size_small"){
            return a.dataset.size - b.dataset.size;
        }

        if(sortValue === "size_large"){
            return b.dataset.size - a.dataset.size;
        }

        return 0;

    });

    files.forEach(file=>{
        grid.appendChild(file);
    });
}

/* ========================= */
/* 🔎 SEARCH FILE + UPLOADER */
/* ========================= */

function searchAll(){

    const searchInput =
    document.getElementById("searchInput");

    if(!searchInput) return;

    const keyword =
    searchInput.value.toLowerCase();

    const files =
    document.querySelectorAll("#fileGrid .file");

    files.forEach(file=>{

        const fileName =
        file.dataset.name || "";

        const uploader =
        file.dataset.uploader || "";

        if(
            fileName.includes(keyword) ||
            uploader.includes(keyword)
        ){

            file.style.display = "block";

        }else{

            file.style.display = "none";
        }

    });

}   
    
/* ========================= */
/* 🔔 LIVE NOTIFICATION */
/* ========================= */

window.addEventListener("load",()=>{

    if(window.latestNotif){

        showLiveNotification(
            window.latestNotif.title,
            window.latestNotif.message
        );

    }

});

function playNotifSound(){

    const audio = new Audio(
        "https://cdn.pixabay.com/download/audio/2022/03/15/audio_c8c8a73467.mp3"
    );

    audio.volume = 0.4;

    audio.play();

}

setInterval(checkNotification, 3000);

let notifTimeout = null;

function checkNotification(){

    fetch("check_notification.php")
    .then(res => res.json())
    .then(data => {

        if(data.success){

            showLiveNotification(
                data.title,
                data.message
            );

        }

    });

}

function showLiveNotification(title, message){

    const live =
    document.getElementById("liveNotif");

    live.innerHTML = "";

    const card = document.createElement("div");

    card.className = "live-card";
    card.id = "liveCard";

    card.innerHTML = `
        <div class="live-top">

            <strong>
                🔔 ${title}
            </strong>

            <span class="close-live">
                ✕
            </span>

        </div>

        <div>
            ${message}
        </div>
    `;

    live.appendChild(card);

    card
    .querySelector(".close-live")
    .addEventListener("click", closeLiveNotif);

    playNotifSound();

    if(notifTimeout){
        clearTimeout(notifTimeout);
    }

    notifTimeout = setTimeout(() => {

        closeLiveNotif();

    }, 5000);
}

function closeLiveNotif(){

    const card =
    document.getElementById("liveCard");

    if(card){
        card.remove();
    }

    if(notifTimeout){
        clearTimeout(notifTimeout);
    }
}