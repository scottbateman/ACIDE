/*
 *  Copyright (c) Codiad & Kent Safranski (codiad.com), distributed
 *  as-is and without warranty under the MIT License. See
 *  [root]/license.txt for more. This information must remain intact.
 */

(function(global, $){

    var codiad = global.codiad;

    $(function() {
        codiad.user.init();
    });

    codiad.user = {

        loginForm: $('#login'),
        controller: 'components/user/controller.php',
        dialog: 'components/user/dialog.php',

        //////////////////////////////////////////////////////////////////
        // Initilization
        //////////////////////////////////////////////////////////////////

        init: function() {
            var _this = this;
            this.loginForm.on('submit', function(e) {
                e.preventDefault();
                _this.authenticate();
            });
            
            // Language Selector
            $('.show-language-selector').click(function(){
                $(this).hide();
                $('.language-selector').animate({height:'toggle'}, "fast");
            });
        },

        //////////////////////////////////////////////////////////////////
        // Authenticate User
        //////////////////////////////////////////////////////////////////

        authenticate: function() {
            $.post(this.controller + '?action=authenticate', this.loginForm.serialize(), function(data) {
                parsed = codiad.jsend.parse(data);
                if (parsed != 'error') {
                    // Session set, reload
                    window.location.reload();
                }
            });
        },

        //////////////////////////////////////////////////////////////////
        // Logout
        //////////////////////////////////////////////////////////////////

        logout: function() {
            $.get(this.controller + '?action=logout', function() {
                window.location.reload();
            });
        },

        //////////////////////////////////////////////////////////////////
        // Open the user manager dialog
        //////////////////////////////////////////////////////////////////

        list: function() {
            $('#modal-content form')
                .die('submit'); // Prevent form bubbling
            codiad.modal.load(400, this.dialog + '?action=list');
        },

        //////////////////////////////////////////////////////////////////
        // Create User
        //////////////////////////////////////////////////////////////////

        createNew: function() {
            var _this = this;
            codiad.modal.load(400, this.dialog + '?action=create');
            $('#modal-content form')
                .live('submit', function(e) {
                e.preventDefault();
                var username = $('#modal-content form input[name="username"]')
                    .val();
                var type = $('#modal-content form select[name="type"]')
                    .val();
                var course = $('#modal-content form select[name="course"]')
                    .val();
                var email = $('#modal-content form input[name="email"]')
                    .val();
                var password1 = $('#modal-content form input[name="password1"]')
                    .val();
                var password2 = $('#modal-content form input[name="password2"]')
                    .val();
                if (password1 != password2) {
                    codiad.message.error('Passwords Do Not Match');
                } else {
                    $.post(_this.controller + '?action=create', {'username' : username , 'password' : password1 , 'type' : type , 'course' : course , 'email' : email   }, function(data) {
                        var createResponse = codiad.jsend.parse(data);
                        if (createResponse != 'error') {
                            codiad.message.success('User Account Created');
                            _this.list();
                        }
                    });
                }
            });
        },
        
        //////////////////////////////////////////////////////////////////
        // Create User
        //////////////////////////////////////////////////////////////////

        edit: function(username) {
            var _this = this;
            codiad.modal.load(400, this.dialog + '?action=edit&username=' + username);
            $('#modal-content form')
                .live('submit', function(e) {
                e.preventDefault();
                var username = $('#modal-content form input[name="username"]')
                    .val();
                var type = $('#modal-content form select[name="type"]')
                    .val();
                var course = $('#modal-content form select[name="course"]')
                    .val();
                var email = $('#modal-content form input[name="email"]')
                    .val();
                
                $.post(_this.controller + '?action=edit', {'username' : username , 'type' : type , 'course' : course , 'email' : email   }, function(data) {
                    var createResponse = codiad.jsend.parse(data);
                    if (createResponse != 'error') {
                        codiad.message.success('User Edited with success');
                        _this.list();
                    }
                });
            });
        },

        //////////////////////////////////////////////////////////////////
        // Delete User
        //////////////////////////////////////////////////////////////////

        delete: function(username) {
            var _this = this;
            codiad.modal.load(400, this.dialog + '?action=delete&username=' + username);
            $('#modal-content form')
                .live('submit', function(e) {
                e.preventDefault();
                var username = $('#modal-content form input[name="username"]')
                    .val();
                $.get(_this.controller + '?action=delete&username=' + username, function(data) {
                    var deleteResponse = codiad.jsend.parse(data);
                    if (deleteResponse != 'error') {
                        codiad.message.success('Account Deleted')
                        _this.list();
                    }
                });
            });
        },

        //////////////////////////////////////////////////////////////////
        // Set Project Access
        //////////////////////////////////////////////////////////////////

        projects: function(username) {
            codiad.modal.load(400, this.dialog + '?action=projects&username=' + username);
            var _this = this;
            $('#modal-content form')
                .live('submit', function(e) {
                e.preventDefault();
                var username = $('#modal-content form input[name="username"]')
                    .val();
                var accessLevel = $('#modal-content form select[name="access_level"]')
                    .val();
                var projects = new Array();
                $('input:checkbox[name="project"]:checked').each(function(){
                    projects.push($(this).val());
                });
                if(accessLevel==0){ projects = 0; }
                // Check and make sure if access level not full that at least on project is selected
                if (accessLevel==1 && !projects) {
                    codiad.message.error('At Least One Project Must Be Selected');
                } else {
                    $.post(_this.controller + '?action=project_access&username=' + username,{projects: projects}, function(data) {
                        var projectsResponse = codiad.jsend.parse(data);
                        if (projectsResponse != 'error') {
                            codiad.message.success('Account Modified');
                        }
                    });
                }
            });
        },

        //////////////////////////////////////////////////////////////////
        // Change Password
        //////////////////////////////////////////////////////////////////

        password: function(username) {
            var _this = this;
            codiad.modal.load(400, this.dialog + '?action=password&username=' + username);
            $('#modal-content form')
                .live('submit', function(e) {
                e.preventDefault();
                var username = $('#modal-content form input[name="username"]')
                    .val();
                var password1 = $('#modal-content form input[name="password1"]')
                    .val();
                var password2 = $('#modal-content form input[name="password2"]')
                    .val();
                if (password1 != password2) {
                    codiad.message.error('Passwords Do Not Match');
                } else {
                    $.post(_this.controller + '?action=password', {'username' : username , 'password' : password1 }, function(data) {
                        var passwordResponse = codiad.jsend.parse(data);
                        if (passwordResponse != 'error') {
                            codiad.message.success('Password Changed');
                            codiad.modal.unload();
                        }
                    });
                }
            });
        },

        //////////////////////////////////////////////////////////////////
        // Change Current Project
        //////////////////////////////////////////////////////////////////

        project: function(project) {
            $.get(this.controller + '?action=project&project=' + project);
        }

    };

})(this, jQuery);