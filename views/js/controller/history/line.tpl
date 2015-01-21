<tr>
    <td>
        <div>
            <div class="first">{{id}}</div>
            <div class="">{{message}}</div>
        </div>
        <div class="secondary">
            <div class="first">{{modified}}</div>
            <div class="">{{__ "by"}} {{author}}</div>
        </div>
    </td>
    <td class="button">
        <button type="button" class="small restore_revision tooltip btn-link" data-revision="{{id}}">
            <span class="icon-repository-add"></span>{{__ "Restore"}}
        </button>
    </td>
</tr>
