from ai_service.main import handoff_reason


def test_common_business_questions_do_not_handoff() -> None:
    assert handoff_reason("คอนโดอยู่ได้ 3 คนไหม") is None
    assert handoff_reason("ชำระเงินยังไงครับ") is None
    assert handoff_reason("จ่ายเงินผ่านช่องทางไหนได้บ้าง") is None
    assert handoff_reason("ต่อรองราคาได้ไหม") is None


def test_explicit_handoff_and_risk_phrases() -> None:
    assert handoff_reason("ขอคุยกับเจ้าหน้าที่") == "customer_request"
    assert handoff_reason("อยากร้องเรียนเรื่องบริการ") == "complaint"
    assert handoff_reason("โอนแล้วไม่เข้าครับ") == "payment_problem"
    assert handoff_reason("ขอคืนเงิน") == "payment_problem"
