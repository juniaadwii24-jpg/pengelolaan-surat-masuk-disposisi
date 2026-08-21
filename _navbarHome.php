<div class="container-fluid">
    <nav class="navbar navbar-expand navbar-white navbar-light">

    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navCustom">
        <span class="navbar-toggler-icon"></span>
</button>
    <div class="collapse navbar-collapse" id="navbarCustom">

    <ul class="navbar-nav mr-auto">
            <li class="nav-item">
                <a href="<?=base_url('home'); ?>" class="nav-link active-link" style="padding-left:0;">Home</a>
</li> 
</ul> 
        
           <ul class="navbar-nav ml-auto">
            <li class="nav-item">
        </ul>
        </div>
</nav>
</div>

<style>

    .custom-navbar{
        backgound: #fffdfd;
        border-radius: 10px;
        padding: 10px 20px;
        box-shadow: 0 2px 8px  rgba(0,0,0,0.05)
        display: flex;
        justify-content: space-between;
    }

    .nav-link{
        color: #555 !important;
        margin-right: 15px;
        position: relative;
        transition: 0.3s;
        font-weight: 500;
    }

    .nav-link::after{
    content:"";
     position: absolute;
     left: 0;
     bottom: 0;
     width: 0%;
     height: 2px;
     background : #007bFf;
     transition: 0.35;
    }


    .nav-link:hover::after {    
        width: 100%;
    }

    .nav-link:hover {    
     color: #007bFf !important;
    }

    
    .active-link:hover {    
        font-weight: 600;
     color: #007bFf !important;
    }
 </style>