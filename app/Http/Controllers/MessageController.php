<?php

namespace App\Http\Controllers;

use App\Jobs\MessageAcceptedJob;
use App\Models\Enumerations\MessageType;
use App\Models\Enumerations\Status;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{

    /**
     * 消息列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $role = $user->roles[0]['name'];
        $company = $user->company;

        $messageType = [];

        $inputType = $request->input('type');

        $absRole = str_replace($company->id . '_', '', $role);

        switch ($absRole) {
            case 'admin':
                $messageType = [
                    MessageType::NewOrder->value,
                    MessageType::NewCheckTask->value,
                    MessageType::ConfirmedPrice->value,
                    MessageType::OrderClosed->value,
                ];
                break;
            case '公司管理员':
                $messageType = [MessageType::NewOrder->value];
                break;
            case '施工经理':
                break;
            case '施工人员':
                break;
            case '查勘经理':
                $messageType = [MessageType::NewOrder->value];
                break;
            case '查勘人员':
                $messageType = [MessageType::NewCheckTask->value];
                break;
            case '财务经理':
                break;
            case '财务人员':
                break;
            case '调度内勤':
                break;
            case '出纳人员':
                break;
            case '造价员' :
                break;
            default:
                $messageType = [];
        }

        $messages = Message::with(['sendCompany:id,name', 'order'])
            ->where('to_company_id', $company->id)
            ->when(strlen($status = $request->input('status')), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($request->input('order_number'), function ($query, $order_number) {
                $query->where('order_number', 'like', '%' . $order_number . '%');
            })
            ->where(function ($query) use ($inputType, $messageType) {
                if ($inputType && in_array($inputType, $messageType))
                    return $query->where('type', $inputType);

                return $query->where('type', $messageType);
            })
            ->where(function ($query) use ($absRole, $user) {
                if (!in_array($absRole, ['公司管理员', '施工经理', '查勘经理'])) {
                    $query->where('user_id', $user->id);
                }
            })
            ->when($request->input('date'), function ($query, $date) {
                $query->where('created_at', '>=', $date)->where('created_at', '<=', $date . ' 23:59:59');
            })
            ->orderBy('id', 'desc')
            ->paginate(getPerPage());

        return success($messages);
    }

    /**
     * 接受消息
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function accept(Request $request): JsonResponse
    {
        $user = $request->user();

        $message = Message::find($request->input('id'));

        if (empty($message) or $message->to_company_id != $user->company_id) return fail('操作失败');

        if ($message->status == Status::Normal->value) return success();

        if (!empty($message->user_id) and $message->user_id != $user->id) return fail('非本人消息');

        $message->status = Status::Normal->value;
        $message->accept_user_id = $user->id;
        $message->accept_at = now()->toDateTimeString();
        $message->save();

        MessageAcceptedJob::dispatch($message);

        return success();
    }
}
