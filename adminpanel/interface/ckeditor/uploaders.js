
function MVframeworkFileUploadPlugin(editor)
{
    editor.ui.componentFactory.add('fileUploadButton', locale => {

        const boldButton = editor.ui.componentFactory.create('bold');
        const ButtonViewClass = boldButton.constructor;
        const view = new ButtonViewClass(locale);

        let icon = '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">';
        icon += '<path d="M5.10675 7.09949H9.14062C9.33828 7.09949 9.5 6.93928 9.5 6.74347C9.5 6.54766 9.33828 6.38745 9.14062 6.38745H5.10675C4.90909 6.38745 4.74738 6.54766 4.74738 6.74347C4.74738 6.93928 4.90909 7.09949 5.10675 7.09949ZM5.10675 9.2997H13.5018C13.6994 9.2997 13.8611 9.13949 13.8611 8.94368C13.8611 8.74787 13.6994 8.58766 13.5018 8.58766H5.10675C4.90909 8.58766 4.74738 8.74787 4.74738 8.94368C4.74738 9.13949 4.90909 9.2997 5.10675 9.2997ZM5.10675 11.4999H11.6258C11.8235 11.4999 11.9852 11.3397 11.9852 11.1439C11.9852 10.9481 11.8235 10.7879 11.6258 10.7879H5.10675C4.90909 10.7879 4.74738 10.9481 4.74738 11.1439C4.74738 11.3397 4.90909 11.4999 5.10675 11.4999ZM9.7355 12.9881H5.11034C4.91269 12.9881 4.75097 13.1483 4.75097 13.3441C4.75097 13.5399 4.91269 13.7001 5.11034 13.7001H9.7355C9.93316 13.7001 10.0949 13.5399 10.0949 13.3441C10.0949 13.1483 9.93316 12.9881 9.7355 12.9881Z" fill="black"/>';
        icon += '<path d="M9.5 16H4V2.26929L10.3497 2.2693V6.37128C10.3497 6.56709 10.5114 6.7273 10.7091 6.7273H14.8497V8.50001C15.3497 8.00001 15.8497 8.00001 16.5 8.50001V6.04482C16.5 5.95226 16.4641 5.85969 16.3958 5.79561L11.7059 1.10681C11.6727 1.07317 11.633 1.04641 11.5892 1.02808C11.5454 1.00974 11.4983 1.0002 11.4508 1.00001H3.5C3 1.02808 2.5 1.5 2.46173 2V16.5C2.5 17 3 17.5 3.5 17.5H11C10.2247 17.5 9.5 17 9.5 16ZM11.8102 3.25997L13.8497 5.2413H11.8102V3.25997Z" fill="black"/>';
        icon += '<path d="M15.522 19.1C15.7315 19.1 15.9325 19.0167 16.0806 18.8686C16.2288 18.7204 16.312 18.5195 16.312 18.31V12.937L18.371 15.392C18.4377 15.4715 18.5193 15.5371 18.6113 15.585C18.7033 15.633 18.8039 15.6624 18.9072 15.6715C19.0106 15.6806 19.1147 15.6692 19.2137 15.6381C19.3126 15.6069 19.4045 15.5566 19.484 15.49C19.5635 15.4233 19.6291 15.3417 19.6771 15.2497C19.7251 15.1577 19.7544 15.0571 19.7635 14.9538C19.7726 14.8504 19.7613 14.7463 19.7301 14.6473C19.699 14.5483 19.6487 14.4565 19.582 14.377L16.23 10.382C16.1112 10.2405 15.9467 10.1452 15.7649 10.1125C15.5831 10.0798 15.3956 10.1118 15.235 10.203C15.1191 10.2516 15.0165 10.3274 14.936 10.424L11.586 14.414C11.4512 14.5744 11.3856 14.7819 11.4036 14.9907C11.4217 15.1995 11.5221 15.3926 11.6825 15.5275C11.843 15.6623 12.0504 15.7279 12.2592 15.7098C12.4681 15.6917 12.6611 15.5914 12.796 15.431L14.732 13.125V18.31C14.732 18.746 15.085 19.1 15.522 19.1Z" fill="black"/>';
        icon += '</svg>';

        view.set({
            label: MVobject.locale('upload_file'),
            icon: icon,
            tooltip: true,
            withText: false
        });

        const input = document.createElement('input');
        input.type = 'file';

        view.on('execute', () => input.click());

        input.onchange = function(event)
        {
            const file = event.target.files[0];
            
            if(!file)
            {
                input.value = ''; 
                return;
            }

            const url = MVobject.adminPanelPath + '?ajax=upload-editor&type=file&token=' + editorSecurityToken;
            const formData = new FormData();
            formData.append('upload', file);
                        
            fetch(url, {method: 'POST', body: formData})
                .then(response => response.json())
                .then(data => 
                {
                    if(typeof data.error != 'undefined')
                        alert(data.error.message);
                    else
                    {
                        editor.model.change(writer =>
                        {
                            const selection = editor.model.document.selection;
                            const selected_content = editor.data.stringify(editor.model.getSelectedContent(selection));

                            if($.trim(selected_content))
                                writer.setAttribute('linkHref', data.url, editor.model.document.selection.getFirstRange());
                            else
                            {
                                editor.model.insertContent(writer.createText(data.name, {linkHref: data.url}));
                                editor.model.insertContent(writer.createText(' '));
                            }
                        });
                    }
                });
            
            input.value = ''; 
        };

        return view;
    });
}