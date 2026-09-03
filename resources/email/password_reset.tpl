{include file='header.tpl'}

<body style="background-color:#EEEEEE;">
    <div style="text-align: center">
        <div border="0" cellpadding="0" cellspacing="0" width="100%" style="padding-top:30px;table-layout:fixed;background-color:#EEEEEE;">
            <div align="center" valign="top" style="padding-right:10px;padding-left:10px;">
                <div border="0" cellpadding="0" cellspacing="0"
                     style="background-color:#FFFFFF;max-width:600px;text-align: center" width="100%">
                    <div align="center" valign="top">
                        <div border="0" cellpadding="0" cellspacing="0" width="100%">
                            <div align="center" valign="middle" style="padding-top:60px;padding-bottom:60px;">
                                <h2 class="bigTitle">
                                    Đặt lại mật khẩu
                                </h2>
                            </div>
                        </div>
                        <div border="0" cellpadding="0" cellspacing="0" style="background-color:#FFFFFF" width="100%">
                            <div align="center" valign="top" style="padding-bottom:60px;padding-left:20px;padding-right:20px;">
                                <p class="midText">
                                    Bạn nhận được email này vì bạn đã yêu cầu đặt lại mật khẩu trên hệ thống {$config['appName']}. Nếu không phải bạn thực hiện, vui lòng bỏ qua email này.
                                    <br><br>
                                    <a href="{$resetUrl}" style="color:#505050" target="_blank">Nhấp vào liên kết này để đặt lại mật khẩu</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

{include file='footer.tpl'}
