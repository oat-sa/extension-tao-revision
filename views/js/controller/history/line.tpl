<tr>
    <td>
        <div>
            <div class="col-2">{{id}}</div>
            <div class="col-8">{{message}}</div>
        </div>
        <div class="secondary">
            <div class="col-6">{{modified}}</div>
            <div class="col-6">{{__ "by"}} {{author}}</div>
        </div>
    </td>
    <td class="numeric">
        <button type="button" class="small restore_revision tooltip btn-link" data-revision="{{id}}">
            <span class="icon-restore"></span>{{__ "Restore"}}
        </button>
    </td>
</tr>
